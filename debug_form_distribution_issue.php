<?php
/**
 * Debug Form Distribution Issue
 * 
 * This script investigates why only 1 clearance form was created instead of 60+
 */

require_once 'includes/config/database.php';

try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    echo "<h1>🔍 Form Distribution Debug Report</h1>\n";
    echo "<p><strong>Generated:</strong> " . date('Y-m-d H:i:s') . "</p>\n";
    
    // 1. Check current clearance forms
    echo "<h2>📋 Current Clearance Forms</h2>\n";
    $stmt = $connection->query("SELECT COUNT(*) as total FROM clearance_forms");
    $totalForms = $stmt->fetchColumn();
    echo "<p><strong>Total Clearance Forms:</strong> $totalForms</p>\n";
    
    if ($totalForms > 0) {
        $stmt = $connection->query("
            SELECT 
                cf.clearance_form_id,
                cf.user_id,
                cf.clearance_type,
                cf.status,
                u.first_name,
                u.last_name,
                s.sector
            FROM clearance_forms cf
            LEFT JOIN users u ON cf.user_id = u.user_id
            LEFT JOIN students s ON cf.user_id = s.user_id
            ORDER BY cf.created_at DESC
            LIMIT 10
        ");
        $forms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
        echo "<tr><th>Form ID</th><th>User ID</th><th>Name</th><th>Type</th><th>Status</th><th>Sector</th></tr>\n";
        foreach ($forms as $form) {
            echo "<tr>";
            echo "<td>{$form['clearance_form_id']}</td>";
            echo "<td>{$form['user_id']}</td>";
            echo "<td>{$form['first_name']} {$form['last_name']}</td>";
            echo "<td>{$form['clearance_type']}</td>";
            echo "<td>{$form['status']}</td>";
            echo "<td>{$form['sector']}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
    // 2. Check students table
    echo "<h2>👥 Students Analysis</h2>\n";
    $stmt = $connection->query("SELECT COUNT(*) as total FROM students");
    $totalStudents = $stmt->fetchColumn();
    echo "<p><strong>Total Students:</strong> $totalStudents</p>\n";
    
    $stmt = $connection->query("
        SELECT 
            sector,
            COUNT(*) as count,
            COUNT(CASE WHEN user_id IS NOT NULL THEN 1 END) as with_user_id,
            COUNT(CASE WHEN enrollment_status = 'Enrolled' THEN 1 END) as enrolled
        FROM students 
        GROUP BY sector
    ");
    $sectorStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
    echo "<tr><th>Sector</th><th>Total</th><th>With User ID</th><th>Enrolled</th></tr>\n";
    foreach ($sectorStats as $stat) {
        echo "<tr>";
        echo "<td>{$stat['sector']}</td>";
        echo "<td>{$stat['count']}</td>";
        echo "<td>{$stat['with_user_id']}</td>";
        echo "<td>{$stat['enrolled']}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // 3. Check clearance periods
    echo "<h2>📅 Clearance Periods</h2>\n";
    $stmt = $connection->query("
        SELECT 
            cp.period_id,
            cp.sector,
            cp.status,
            cp.start_date,
            ay.year as academic_year,
            s.semester_name
        FROM clearance_periods cp
        LEFT JOIN academic_years ay ON cp.academic_year_id = ay.academic_year_id
        LEFT JOIN semesters s ON cp.semester_id = s.semester_id
        ORDER BY cp.created_at DESC
    ");
    $periods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
    echo "<tr><th>Period ID</th><th>Sector</th><th>Status</th><th>Academic Year</th><th>Semester</th><th>Start Date</th></tr>\n";
    foreach ($periods as $period) {
        echo "<tr>";
        echo "<td>{$period['period_id']}</td>";
        echo "<td>{$period['sector']}</td>";
        echo "<td>{$period['status']}</td>";
        echo "<td>{$period['academic_year']}</td>";
        echo "<td>{$period['semester_name']}</td>";
        echo "<td>{$period['start_date']}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // 4. Test the getEligibleUsersForSector function logic
    echo "<h2>🔍 Testing Eligible Users Logic</h2>\n";
    
    // Test College sector
    echo "<h3>College Sector Test</h3>\n";
    $stmt = $connection->prepare("
        SELECT DISTINCT u.user_id, u.first_name, u.last_name, u.username,
               s.section, s.department_id, d.department_name
        FROM users u
        INNER JOIN students s ON u.user_id = s.user_id
        INNER JOIN departments d ON s.department_id = d.department_id
        INNER JOIN sectors sec ON d.sector_id = sec.sector_id
        WHERE sec.sector_name = 'College'
        AND u.status = 'active'
        AND s.enrollment_status = 'Enrolled'
        ORDER BY u.last_name, u.first_name
    ");
    $stmt->execute();
    $collegeStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p><strong>College Students Found:</strong> " . count($collegeStudents) . "</p>\n";
    
    if (count($collegeStudents) > 0) {
        echo "<p><strong>First 5 College Students:</strong></p>\n";
        echo "<ul>\n";
        for ($i = 0; $i < min(5, count($collegeStudents)); $i++) {
            $student = $collegeStudents[$i];
            echo "<li>{$student['first_name']} {$student['last_name']} (ID: {$student['user_id']}, Dept: {$student['department_name']})</li>\n";
        }
        echo "</ul>\n";
    }
    
    // Test Senior High School sector
    echo "<h3>Senior High School Sector Test</h3>\n";
    $stmt = $connection->prepare("
        SELECT DISTINCT u.user_id, u.first_name, u.last_name, u.username,
               s.section, s.department_id, d.department_name
        FROM users u
        INNER JOIN students s ON u.user_id = s.user_id
        INNER JOIN departments d ON s.department_id = d.department_id
        INNER JOIN sectors sec ON d.sector_id = sec.sector_id
        WHERE sec.sector_name = 'Senior High School'
        AND u.status = 'active'
        AND s.enrollment_status = 'Enrolled'
        ORDER BY u.last_name, u.first_name
    ");
    $stmt->execute();
    $shsStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p><strong>SHS Students Found:</strong> " . count($shsStudents) . "</p>\n";
    
    // Test Faculty sector
    echo "<h3>Faculty Sector Test</h3>\n";
    $stmt = $connection->prepare("
        SELECT DISTINCT u.user_id, u.first_name, u.last_name, u.username,
               f.employment_status, f.department_id, d.department_name
        FROM users u
        INNER JOIN faculty f ON u.user_id = f.user_id
        INNER JOIN departments d ON f.department_id = d.department_id
        INNER JOIN sectors sec ON d.sector_id = sec.sector_id
        WHERE sec.sector_name = 'Faculty'
        AND u.status = 'active'
        ORDER BY u.last_name, u.first_name
    ");
    $stmt->execute();
    $faculty = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p><strong>Faculty Found:</strong> " . count($faculty) . "</p>\n";
    
    // 5. Check signatory assignments
    echo "<h2>📝 Signatory Assignments</h2>\n";
    $stmt = $connection->query("
        SELECT 
            clearance_type,
            COUNT(*) as assignment_count
        FROM sector_signatory_assignments 
        WHERE is_active = 1
        GROUP BY clearance_type
    ");
    $signatoryStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
    echo "<tr><th>Clearance Type</th><th>Assignment Count</th></tr>\n";
    foreach ($signatoryStats as $stat) {
        echo "<tr>";
        echo "<td>{$stat['clearance_type']}</td>";
        echo "<td>{$stat['assignment_count']}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // 6. Check clearance signatories
    echo "<h2>👤 Clearance Signatories</h2>\n";
    $stmt = $connection->query("SELECT COUNT(*) as total FROM clearance_signatories");
    $totalSignatories = $stmt->fetchColumn();
    echo "<p><strong>Total Signatory Assignments:</strong> $totalSignatories</p>\n";
    
    if ($totalSignatories > 0) {
        $stmt = $connection->query("
            SELECT 
                cs.clearance_form_id,
                d.designation_name,
                cs.action,
                u.first_name,
                u.last_name
            FROM clearance_signatories cs
            LEFT JOIN designations d ON cs.designation_id = d.designation_id
            LEFT JOIN users u ON cs.actual_user_id = u.user_id
            ORDER BY cs.created_at DESC
            LIMIT 10
        ");
        $signatories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
        echo "<tr><th>Form ID</th><th>Designation</th><th>Action</th><th>Signatory</th></tr>\n";
        foreach ($signatories as $sig) {
            echo "<tr>";
            echo "<td>{$sig['clearance_form_id']}</td>";
            echo "<td>{$sig['designation_name']}</td>";
            echo "<td>{$sig['action']}</td>";
            echo "<td>{$sig['first_name']} {$sig['last_name']}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
    // 7. Summary and recommendations
    echo "<h2>📊 Summary & Recommendations</h2>\n";
    echo "<div style='background: #f0f8ff; padding: 15px; border-radius: 8px; margin: 10px 0;'>\n";
    echo "<h3>Issues Found:</h3>\n";
    echo "<ul>\n";
    
    if ($totalForms < $totalStudents) {
        echo "<li><strong>❌ Form Distribution Issue:</strong> Only $totalForms forms created for $totalStudents students</li>\n";
    }
    
    if ($totalSignatories == 0) {
        echo "<li><strong>❌ Signatory Assignment Issue:</strong> No signatories assigned to clearance forms</li>\n";
    }
    
    echo "</ul>\n";
    
    echo "<h3>Next Steps:</h3>\n";
    echo "<ul>\n";
    echo "<li>1. Check if form distribution was triggered properly</li>\n";
    echo "<li>2. Verify sector signatory assignments exist</li>\n";
    echo "<li>3. Re-run form distribution for all sectors</li>\n";
    echo "<li>4. Test the user_status.php API with existing forms</li>\n";
    echo "</ul>\n";
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>\n";
    echo "<p>Error: " . $e->getMessage() . "</p>\n";
}
?>
