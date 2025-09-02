<?php
// Create Missing Clearance Tables
require_once 'includes/config/database.php';

try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    echo "<h2>🔧 Creating Missing Clearance Tables</h2>\n";
    
    // Start transaction
    $connection->beginTransaction();
    
    // 1. Create clearance_periods table
    echo "<h3>📅 Creating clearance_periods table...</h3>\n";
    $sql = "CREATE TABLE IF NOT EXISTS clearance_periods (
        period_id INT PRIMARY KEY AUTO_INCREMENT,
        academic_year_id INT NOT NULL,
        semester_id INT NOT NULL,
        period_name VARCHAR(100) NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        is_active BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (academic_year_id) REFERENCES academic_years(academic_year_id),
        FOREIGN KEY (semester_id) REFERENCES semesters(semester_id),
        UNIQUE KEY unique_active_period (is_active),
        CHECK (is_active IN (0, 1))
    )";
    
    $connection->exec($sql);
    echo "<p style='color: green;'>✅ clearance_periods table created/verified</p>\n";
    
    // 2. Create clearance_requirements table (if it doesn't exist with correct structure)
    echo "<h3>📋 Creating clearance_requirements table...</h3>\n";
    $sql = "CREATE TABLE IF NOT EXISTS clearance_requirements (
        requirement_id INT PRIMARY KEY AUTO_INCREMENT,
        clearance_type ENUM('Student', 'Faculty') NOT NULL,
        designation_id INT NOT NULL,
        is_required BOOLEAN DEFAULT TRUE,
        is_department_specific BOOLEAN DEFAULT FALSE,
        applies_to_departments JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (designation_id) REFERENCES designations(designation_id)
    )";
    
    $connection->exec($sql);
    echo "<p style='color: green;'>✅ clearance_requirements table created/verified</p>\n";
    
    // 3. Create clearance_applications table
    echo "<h3>📝 Creating clearance_applications table...</h3>\n";
    $sql = "CREATE TABLE IF NOT EXISTS clearance_applications (
        application_id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        period_id INT NOT NULL,
        status ENUM('pending', 'in-progress', 'completed', 'rejected') DEFAULT 'pending',
        applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        completed_at TIMESTAMP NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id),
        FOREIGN KEY (period_id) REFERENCES clearance_periods(period_id),
        UNIQUE KEY unique_user_period (user_id, period_id)
    )";
    
    $connection->exec($sql);
    echo "<p style='color: green;'>✅ clearance_applications table created/verified</p>\n";
    
    // 4. Create clearance_signatory_status table
    echo "<h3>📊 Creating clearance_signatory_status table...</h3>\n";
    $sql = "CREATE TABLE IF NOT EXISTS clearance_signatory_status (
        status_id INT PRIMARY KEY AUTO_INCREMENT,
        application_id INT NOT NULL,
        requirement_id INT NOT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        signed_by_user_id INT NULL,
        signed_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (application_id) REFERENCES clearance_applications(application_id),
        FOREIGN KEY (requirement_id) REFERENCES clearance_requirements(requirement_id),
        FOREIGN KEY (signed_by_user_id) REFERENCES users(user_id),
        UNIQUE KEY unique_application_requirement (application_id, requirement_id)
    )";
    
    $connection->exec($sql);
    echo "<p style='color: green;'>✅ clearance_signatory_status table created/verified</p>\n";
    
    // 5. Create rejection_remarks table
    echo "<h3>💬 Creating rejection_remarks table...</h3>\n";
    $sql = "CREATE TABLE IF NOT EXISTS rejection_remarks (
        remark_id INT PRIMARY KEY AUTO_INCREMENT,
        status_id INT NOT NULL,
        reason_id INT NULL,
        remarks_text TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (status_id) REFERENCES clearance_signatory_status(status_id),
        FOREIGN KEY (reason_id) REFERENCES rejection_reasons(reason_id)
    )";
    
    $connection->exec($sql);
    echo "<p style='color: green;'>✅ rejection_remarks table created/verified</p>\n";
    
    // 6. Insert sample clearance requirements (using correct column names)
    echo "<h3>📋 Adding Sample Clearance Requirements...</h3>\n";
    
    // First, get designation IDs
    $stmt = $connection->query("SELECT designation_id, designation_name FROM designations");
    $designations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $designationMap = [];
    foreach ($designations as $designation) {
        $designationMap[$designation['designation_name']] = $designation['designation_id'];
    }
    
    $requirements = [
        ['Student', $designationMap['Librarian'] ?? 1, 1, FALSE, NULL],
        ['Student', $designationMap['Accountant'] ?? 2, 1, FALSE, NULL],
        ['Student', $designationMap['Registrar'] ?? 3, 1, FALSE, NULL],
        ['Student', $designationMap['Student Affairs Officer'] ?? 4, 1, FALSE, NULL],
        ['Student', $designationMap['MIS/IT Staff'] ?? 5, 1, FALSE, NULL],
        ['Student', $designationMap['Program Head'] ?? 6, 1, TRUE, '["ICT", "Business", "Engineering"]'],
        ['Faculty', $designationMap['Librarian'] ?? 1, 1, FALSE, NULL],
        ['Faculty', $designationMap['Accountant'] ?? 2, 1, FALSE, NULL],
        ['Faculty', $designationMap['Registrar'] ?? 3, 1, FALSE, NULL],
        ['Faculty', $designationMap['Student Affairs Officer'] ?? 4, 1, FALSE, NULL],
        ['Faculty', $designationMap['MIS/IT Staff'] ?? 5, 1, FALSE, NULL]
    ];
    
    foreach ($requirements as $req) {
        $stmt = $connection->prepare("
            INSERT IGNORE INTO clearance_requirements 
            (clearance_type, designation_id, is_required, is_department_specific, applies_to_departments) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute($req);
    }
    
    echo "<p style='color: green;'>✅ Sample clearance requirements added</p>\n";
    
    // 7. Insert sample clearance period
    echo "<h3>📅 Adding Sample Clearance Period...</h3>\n";
    
    $stmt = $connection->prepare("
        INSERT IGNORE INTO clearance_periods 
        (academic_year_id, semester_id, period_name, start_date, end_date, is_active) 
        VALUES (1, 1, '2024-2025 First Semester Clearance', '2024-06-01', '2024-10-31', TRUE)
    ");
    $stmt->execute();
    
    echo "<p style='color: green;'>✅ Sample clearance period added</p>\n";
    
    // Commit transaction
    $connection->commit();
    
    echo "<p style='color: green; font-weight: bold;'>🎉 All missing clearance tables created successfully!</p>\n";
    echo "<p>The database now has all necessary tables for clearance management.</p>\n";
    
} catch (Exception $e) {
    if ($connection->inTransaction()) { 
        $connection->rollBack(); 
    }
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>\n";
} catch (PDOException $e) {
    if ($connection->inTransaction()) { 
        $connection->rollBack(); 
    }
    echo "<p style='color: red;'>❌ Database Error: " . $e->getMessage() . "</p>\n";
}
?>
