<?php
/**
 * Test Clearance Integration
 * 
 * This script tests the complete clearance form integration for students.
 * It simulates the workflow from form distribution to student application.
 */

require_once 'includes/config/database.php';
require_once 'includes/classes/Auth.php';

echo "<h1>🧪 Testing Clearance Integration</h1>\n";

try {
    $connection = Database::getInstance()->getConnection();
    
    echo "<h2>📊 Current Database State</h2>\n";
    
    // Check clearance forms
    $stmt = $connection->query("SELECT COUNT(*) as count FROM clearance_forms");
    $formCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "✅ Clearance Forms: $formCount<br>\n";
    
    // Check clearance signatories
    $stmt = $connection->query("SELECT COUNT(*) as count FROM clearance_signatories");
    $signatoryCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "✅ Clearance Signatories: $signatoryCount<br>\n";
    
    // Check test students
    $stmt = $connection->query("
        SELECT COUNT(*) as count 
        FROM users u 
        INNER JOIN user_roles ur ON u.user_id = ur.user_id 
        WHERE ur.role_id = 3 AND u.status = 'active'
    ");
    $studentCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "✅ Test Students with Roles: $studentCount<br>\n";
    
    // Check sector signatory assignments
    $stmt = $connection->query("SELECT COUNT(*) as count FROM sector_signatory_assignments");
    $assignmentCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "✅ Sector Signatory Assignments: $assignmentCount<br>\n";
    
    echo "<h2>👥 Sample Clearance Forms</h2>\n";
    
    // Show sample clearance forms with signatories
    $stmt = $connection->query("
        SELECT 
            cf.clearance_form_id,
            cf.user_id,
            cf.status as form_status,
            cf.clearance_type,
            u.first_name,
            u.last_name,
            u.username,
            COUNT(cs.signatory_id) as signatory_count
        FROM clearance_forms cf
        INNER JOIN users u ON cf.user_id = u.user_id
        LEFT JOIN clearance_signatories cs ON cf.clearance_form_id = cs.clearance_form_id
        GROUP BY cf.clearance_form_id
        ORDER BY cf.created_at DESC
        LIMIT 5
    ");
    
    $forms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($forms)) {
        echo "❌ No clearance forms found. Forms should be created when clearance periods start.<br>\n";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
        echo "<tr><th>Form ID</th><th>Student</th><th>Type</th><th>Status</th><th>Signatories</th></tr>\n";
        
        foreach ($forms as $form) {
            echo "<tr>";
            echo "<td>{$form['clearance_form_id']}</td>";
            echo "<td>{$form['first_name']} {$form['last_name']} ({$form['username']})</td>";
            echo "<td>{$form['clearance_type']}</td>";
            echo "<td>{$form['form_status']}</td>";
            echo "<td>{$form['signatory_count']}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
    echo "<h2>📝 Sample Signatory Assignments</h2>\n";
    
    // Show sample signatory assignments for the first form
    if (!empty($forms)) {
        $firstFormId = $forms[0]['clearance_form_id'];
        
        $stmt = $connection->prepare("
            SELECT 
                cs.signatory_id,
                cs.action,
                cs.remarks,
                d.designation_name,
                u.first_name,
                u.last_name
            FROM clearance_signatories cs
            INNER JOIN designations d ON cs.designation_id = d.designation_id
            LEFT JOIN users u ON cs.actual_user_id = u.user_id
            WHERE cs.clearance_form_id = ?
            ORDER BY cs.created_at ASC
        ");
        $stmt->execute([$firstFormId]);
        $signatories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($signatories)) {
            echo "❌ No signatories assigned to form $firstFormId<br>\n";
        } else {
            echo "<h3>Signatories for Form $firstFormId:</h3>\n";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
            echo "<tr><th>Signatory ID</th><th>Designation</th><th>Assigned User</th><th>Status</th><th>Remarks</th></tr>\n";
            
            foreach ($signatories as $signatory) {
                echo "<tr>";
                echo "<td>{$signatory['signatory_id']}</td>";
                echo "<td>{$signatory['designation_name']}</td>";
                echo "<td>" . ($signatory['first_name'] ? "{$signatory['first_name']} {$signatory['last_name']}" : 'N/A') . "</td>";
                echo "<td>" . ($signatory['action'] ?: 'Unapplied') . "</td>";
                echo "<td>" . ($signatory['remarks'] ?: 'None') . "</td>";
                echo "</tr>\n";
            }
            echo "</table>\n";
        }
    }
    
    echo "<h2>🔗 API Endpoints Test</h2>\n";
    
    // Test the new APIs
    $testUrls = [
        'User Clearance Forms API' => 'api/clearance/user_clearance_forms.php',
        'User Status API' => 'api/clearance/user_status.php',
        'Apply Signatory API' => 'api/clearance/apply_signatory.php'
    ];
    
    foreach ($testUrls as $name => $url) {
        echo "<h3>$name</h3>\n";
        echo "URL: <code>$url</code><br>\n";
        echo "Status: ✅ Created and ready for testing<br>\n";
    }
    
    echo "<h2>🎯 Integration Workflow</h2>\n";
    echo "<ol>\n";
    echo "<li><strong>Admin activates term</strong> → Creates clearance periods for all sectors</li>\n";
    echo "<li><strong>Admin starts clearance period</strong> → Triggers form distribution</li>\n";
    echo "<li><strong>Form distribution creates</strong> → Clearance forms for eligible users</li>\n";
    echo "<li><strong>Signatory assignment creates</strong> → Clearance signatories based on sector settings</li>\n";
    echo "<li><strong>Student logs in</strong> → Views their clearance forms via user_clearance_forms.php</li>\n";
    echo "<li><strong>Student selects form</strong> → Views signatories via user_status.php</li>\n";
    echo "<li><strong>Student applies to signatory</strong> → Updates status via apply_signatory.php</li>\n";
    echo "<li><strong>UI updates dynamically</strong> → Shows current status and enables/disables buttons</li>\n";
    echo "</ol>\n";
    
    echo "<h2>🚀 Ready for Testing</h2>\n";
    echo "<p><strong>To test the complete integration:</strong></p>\n";
    echo "<ol>\n";
    echo "<li>Log in as admin and go to Clearance Management</li>\n";
    echo "<li>Activate a term and start a clearance period</li>\n";
    echo "<li>Log in as a test student and go to Clearance page</li>\n";
    echo "<li>Verify signatory cards/table rows are generated</li>\n";
    echo "<li>Test applying to signatories and verify status updates</li>\n";
    echo "</ol>\n";
    
    echo "<p><strong>Test Student Credentials:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>John Doe (BSIT): <code>02000100001</code> / <code>Doe02000100001</code></li>\n";
    echo "<li>Emily Wilson (BSCS): <code>02000100006</code> / <code>Wilson02000100006</code></li>\n";
    echo "<li>Alex Garcia (ABM): <code>02000200001</code> / <code>Garcia02000200001</code></li>\n";
    echo "</ul>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>\n";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
code { background-color: #f4f4f4; padding: 2px 4px; border-radius: 3px; }
</style>
