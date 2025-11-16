<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Relationship Diagram</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #1a1a2e;
            color: #eee;
            padding: 20px;
        }
        .container {
            max-width: 1600px;
            margin: 0 auto;
        }
        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #00d9ff;
            font-size: 2.5em;
            text-shadow: 0 0 10px rgba(0, 217, 255, 0.5);
        }
        .controls {
            background: #16213e;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        .filter-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        label {
            color: #00d9ff;
            font-weight: 600;
        }
        select, input {
            padding: 8px 15px;
            border: 2px solid #00d9ff;
            background: #0f3460;
            color: #eee;
            border-radius: 5px;
            font-size: 14px;
        }
        select:focus, input:focus {
            outline: none;
            box-shadow: 0 0 10px rgba(0, 217, 255, 0.5);
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        .stat-box h3 {
            font-size: 2em;
            margin-bottom: 5px;
        }
        .diagram-container {
            background: #16213e;
            border-radius: 10px;
            padding: 30px;
            overflow-x: auto;
        }
        .table-group {
            margin-bottom: 40px;
        }
        .table-group h2 {
            color: #00d9ff;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #00d9ff;
        }
        .tables-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .table-card {
            background: #0f3460;
            border: 2px solid #00d9ff;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s;
        }
        .table-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 217, 255, 0.4);
        }
        .table-header {
            background: linear-gradient(135deg, #00d9ff 0%, #667eea 100%);
            padding: 15px;
            color: #1a1a2e;
            font-weight: bold;
            font-size: 1.1em;
        }
        .table-body {
            padding: 15px;
        }
        .pk-list, .fk-list {
            margin: 10px 0;
        }
        .pk-list h4, .fk-list h4 {
            color: #00d9ff;
            font-size: 0.9em;
            margin-bottom: 8px;
        }
        .key-item {
            background: #1a1a2e;
            padding: 8px 12px;
            margin: 5px 0;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 0.85em;
            border-left: 3px solid #00d9ff;
        }
        .fk-item {
            border-left-color: #ff6b6b;
        }
        .fk-reference {
            color: #00d9ff;
            font-weight: 600;
        }
        .delete-action {
            display: inline-block;
            margin-left: 10px;
            padding: 2px 8px;
            background: #764ba2;
            border-radius: 3px;
            font-size: 0.75em;
        }
        .search-highlight {
            background: #ffeb3b;
            color: #1a1a2e;
            padding: 2px 4px;
            border-radius: 3px;
        }
        .no-relationships {
            text-align: center;
            padding: 40px;
            color: #ff6b6b;
            font-size: 1.2em;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗺️ Database Relationship Diagram</h1>
        
        <?php
        require_once 'includes/config/database.php';
        
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
            
            // Get statistics
            $stmt = $pdo->query("
                SELECT COUNT(*) as count 
                FROM information_schema.TABLES 
                WHERE TABLE_SCHEMA = '" . DB_NAME . "'
            ");
            $total_tables = $stmt->fetch()['count'];
            
            $stmt = $pdo->query("
                SELECT COUNT(*) as count
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = '" . DB_NAME . "' 
                  AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            $total_fks = $stmt->fetch()['count'];
            
            // Get all tables with their keys
            $stmt = $pdo->query("
                SELECT 
                    t.TABLE_NAME,
                    GROUP_CONCAT(DISTINCT 
                        CASE WHEN k.CONSTRAINT_NAME = 'PRIMARY' 
                        THEN k.COLUMN_NAME END
                    ) as pk_columns
                FROM information_schema.TABLES t
                LEFT JOIN information_schema.KEY_COLUMN_USAGE k 
                    ON t.TABLE_NAME = k.TABLE_NAME 
                    AND t.TABLE_SCHEMA = k.TABLE_SCHEMA
                WHERE t.TABLE_SCHEMA = '" . DB_NAME . "'
                GROUP BY t.TABLE_NAME
                ORDER BY t.TABLE_NAME
            ");
            $tables = $stmt->fetchAll();
            
            // Get all foreign keys
            $stmt = $pdo->query("
                SELECT 
                    k.TABLE_NAME,
                    k.COLUMN_NAME,
                    k.REFERENCED_TABLE_NAME,
                    k.REFERENCED_COLUMN_NAME,
                    rc.DELETE_RULE
                FROM information_schema.KEY_COLUMN_USAGE k
                JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
                    ON k.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
                    AND k.CONSTRAINT_SCHEMA = rc.CONSTRAINT_SCHEMA
                WHERE k.TABLE_SCHEMA = '" . DB_NAME . "'
                  AND k.REFERENCED_TABLE_NAME IS NOT NULL
                ORDER BY k.TABLE_NAME, k.COLUMN_NAME
            ");
            $all_fks = $stmt->fetchAll();
            
            // Organize FKs by table
            $fks_by_table = [];
            foreach ($all_fks as $fk) {
                $fks_by_table[$fk['TABLE_NAME']][] = $fk;
            }
            
            // Categorize tables
            $categories = [
                'Core User Management' => ['users', 'roles', 'permissions', 'role_permissions', 'user_roles'],
                'Academic Structure' => ['academic_years', 'semesters', 'sectors', 'departments', 'programs', 'designations'],
                'Clearance Management' => ['clearance_periods', 'clearance_forms', 'clearance_requirements', 
                                           'clearance_signatories', 'clearance_signatories_new', 
                                           'signatory_actions', 'rejection_reasons', 'rejection_remarks'],
                'User Types' => ['students', 'faculty', 'staff'],
                'Signatory Assignments' => ['signatory_assignments', 'sector_signatory_assignments', 
                                            'staff_designation_assignments'],
                'Configuration' => ['scope_settings', 'sector_clearance_settings', 'system_settings'],
                'Audit & Tracking' => ['audit_logs', 'user_activities', 'login_sessions', 
                                       'bulk_operations', 'operation_logs'],
                'File Management' => ['file_uploads', 'data_versions']
            ];
            
            ?>
            
            <!-- Statistics -->
            <div class="stats">
                <div class="stat-box">
                    <h3><?php echo $total_tables; ?></h3>
                    <p>Total Tables</p>
                </div>
                <div class="stat-box">
                    <h3><?php echo $total_fks; ?></h3>
                    <p>Foreign Keys</p>
                </div>
                <div class="stat-box">
                    <h3><?php echo count($fks_by_table); ?></h3>
                    <p>Tables with FKs</p>
                </div>
                <div class="stat-box">
                    <h3><?php echo count($categories); ?></h3>
                    <p>Categories</p>
                </div>
            </div>
            
            <!-- Controls -->
            <div class="controls">
                <div class="filter-group">
                    <label for="categoryFilter">Filter by Category:</label>
                    <select id="categoryFilter" onchange="filterByCategory(this.value)">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $catName => $tables): ?>
                            <option value="<?php echo htmlspecialchars($catName); ?>">
                                <?php echo htmlspecialchars($catName); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="searchTable">Search Table:</label>
                    <input type="text" id="searchTable" placeholder="Type table name..." 
                           onkeyup="searchTables(this.value)">
                </div>
            </div>
            
            <!-- Diagram -->
            <div class="diagram-container">
                <?php if ($total_fks === 0): ?>
                    <div class="no-relationships">
                        ❌ No relationships found!<br>
                        <small>Run restore_database_relationships.php to add them.</small>
                    </div>
                <?php else: ?>
                    <?php foreach ($categories as $category => $category_tables): ?>
                        <div class="table-group" data-category="<?php echo htmlspecialchars($category); ?>">
                            <h2><?php echo htmlspecialchars($category); ?></h2>
                            <div class="tables-grid">
                                <?php foreach ($tables as $table): 
                                    if (!in_array($table['TABLE_NAME'], $category_tables)) continue;
                                ?>
                                    <div class="table-card" data-table="<?php echo htmlspecialchars($table['TABLE_NAME']); ?>">
                                        <div class="table-header">
                                            <?php echo htmlspecialchars($table['TABLE_NAME']); ?>
                                        </div>
                                        <div class="table-body">
                                            <?php if ($table['pk_columns']): ?>
                                                <div class="pk-list">
                                                    <h4>🔑 Primary Key:</h4>
                                                    <?php 
                                                    $pks = explode(',', $table['pk_columns']);
                                                    foreach ($pks as $pk): 
                                                    ?>
                                                        <div class="key-item"><?php echo htmlspecialchars($pk); ?></div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (isset($fks_by_table[$table['TABLE_NAME']])): ?>
                                                <div class="fk-list">
                                                    <h4>🔗 Foreign Keys:</h4>
                                                    <?php foreach ($fks_by_table[$table['TABLE_NAME']] as $fk): ?>
                                                        <div class="key-item fk-item">
                                                            <?php echo htmlspecialchars($fk['COLUMN_NAME']); ?>
                                                            →
                                                            <span class="fk-reference">
                                                                <?php echo htmlspecialchars($fk['REFERENCED_TABLE_NAME']); ?>.<?php echo htmlspecialchars($fk['REFERENCED_COLUMN_NAME']); ?>
                                                            </span>
                                                            <span class="delete-action">
                                                                <?php echo htmlspecialchars($fk['DELETE_RULE']); ?>
                                                            </span>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
        <?php
        } catch (PDOException $e) {
            echo '<div style="background: #ff6b6b; padding: 20px; border-radius: 10px;">';
            echo '<h2>Database Connection Error</h2>';
            echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '</div>';
        }
        ?>
    </div>
    
    <script>
        function filterByCategory(category) {
            const groups = document.querySelectorAll('.table-group');
            groups.forEach(group => {
                if (category === '' || group.dataset.category === category) {
                    group.style.display = 'block';
                } else {
                    group.style.display = 'none';
                }
            });
        }
        
        function searchTables(query) {
            query = query.toLowerCase();
            const cards = document.querySelectorAll('.table-card');
            
            cards.forEach(card => {
                const tableName = card.dataset.table.toLowerCase();
                const header = card.querySelector('.table-header');
                
                if (query === '' || tableName.includes(query)) {
                    card.style.display = 'block';
                    
                    // Highlight matching text
                    if (query !== '') {
                        const regex = new RegExp(`(${query})`, 'gi');
                        header.innerHTML = header.textContent.replace(regex, 
                            '<span class="search-highlight">$1</span>');
                    } else {
                        header.innerHTML = header.textContent;
                    }
                } else {
                    card.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>








