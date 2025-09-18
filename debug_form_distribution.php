<?php
/**
 * Debug Form Distribution
 * 
 * This script checks why signatories aren't being assigned during form distribution.
 */

require_once 'includes/config/database.php';

echo "<h1>🔍 Debugging Form Distribution</h1>\n";

try {
    $connection = Database::getInstance()->getConnection();
    
    echo "<h2>📊 Current State Analysis</h2>\n";
    
    // Check clearance forms
    $stmt = $connection->query("SELECT COUNT(*) as count FROM clearance_forms");
    $formCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "✅ Clearance Forms: $formCount<br>\n";
    
    // Check clearance signatories
    $stmt = $connection->query("SELECT COUNT(*) as count FROM clearance_signatories");
    $signatoryCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "✅ Clearance Signatories: $signatoryCount<br>\n";
    
    // Check sector signatory assignments
    $stmt = $connection->query("
        SELECT ssa.*, d.designation_name, u.first_name, u.last_name
        FROM sector_signatory_assignments ssa
        INNER JOIN designations d ON ssa.designation_id = d.designation_id
        INNER JOIN users u ON ssa.user_id = u.user_id
        ORDER BY ssa.clearance_type, d.designation_name
    ");
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>🎯 Sector Signatory Assignments</h2>\n";
    if (empty($assignments)) {
        echo "❌ No sector signatory assignments found!<br>\n";
        echo "<p>This is why signatories aren't being assigned. You need to assign signatories to sectors first.</p>\n";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
        echo "<tr><th>Clearance Type</th><th>Designation</th><th>Signatory</th><th>Is Program Head</th><th>Department ID</th></tr>\n";
        
        foreach ($assignments as $assignment) {
            echo "<tr>";
            echo "<td>{$assignment['clearance_type']}</td>";
            echo "<td>{$assignment['designation_name']}</td>";
            echo "<td>{$assignment['first_name']} {$assignment['last_name']}</td>";
            echo "<td>" . ($assignment['is_program_head'] ? 'Yes' : 'No') . "</td>";
            echo "<td>" . ($assignment['department_id'] ?: 'N/A') . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
    // Check clearance forms and their types
    echo "<h2>📋 Clearance Forms Analysis</h2>\n";
    $stmt = $connection->query("
        SELECT 
            cf.clearance_form_id,
            cf.clearance_type,
            cf.user_id,
            u.first_name,
            u.last_name,
            u.username,
            COUNT(cs.signatory_id) as signatory_count
        FROM clearance_forms cf
        INNER JOIN users u ON cf.user_id = u.user_id
        LEFT JOIN clearance_signatories cs ON cf.clearance_form_id = cs.clearance_form_id
        GROUP BY cf.clearance_form_id
        ORDER BY cf.created_at DESC
    ");
    $forms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($forms)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
        echo "<tr><th>Form ID</th><th>Type</th><th>User</th><th>Signatory Count</th></tr>\n";
        
        foreach ($forms as $form) {
            echo "<tr>";
            echo "<td>{$form['clearance_form_id']}</td>";
            echo "<td>{$form['clearance_type']}</td>";
            echo "<td>{$form['first_name']} {$form['last_name']} ({$form['username']})</td>";
            echo "<td>{$form['signatory_count']}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
    // Check if there are matching assignments for each form type
    echo "<h2>🔍 Assignment Matching Analysis</h2>\n";
    
    $formTypes = array_unique(array_column($forms, 'clearance_type'));
    
    foreach ($formTypes as $type) {
        echo "<h3>Clearance Type: $type</h3>\n";
        
        // Count forms of this type
        $typeForms = array_filter($forms, fn($f) => $f['clearance_type'] === $type);
        $typeFormCount = count($typeForms);
        
        // Count assignments for this type
        $typeAssignments = array_filter($assignments, fn($a) => $a['clearance_type'] === $type);
        $typeAssignmentCount = count($typeAssignments);
        
        echo "Forms: $typeFormCount<br>\n";
        echo "Assignments: $typeAssignmentCount<br>\n";
        
        if ($typeAssignmentCount === 0) {
            echo "❌ <strong>No signatory assignments found for $type!</strong><br>\n";
            echo "This explains why no signatories are assigned to $type forms.<br>\n";
        } else {
            echo "✅ Assignments available for $type<br>\n";
        }
    }
    
    echo "<h2>🛠️ Recommended Actions</h2>\n";
    
    if (empty($assignments)) {
        echo "<p><strong>Action Required:</strong> You need to assign signatories to sectors first.</p>\n";
        echo "<ol>\n";
        echo "<li>Go to Clearance Management page as admin</li>\n";
        echo "<li>For each sector (College, SHS, Faculty), click 'Add Signatory'</li>\n";
        echo "<li>Assign signatories to each sector</li>\n";
        echo "<li>Then start a clearance period to trigger form distribution</li>\n";
        echo "</ol>\n";
    } else {
        echo "<p><strong>Issue Found:</strong> Forms exist but signatories weren't assigned during distribution.</p>\n";
        echo "<p>This could be due to:</p>\n";
        echo "<ul>\n";
        echo "<li>Form distribution was run before signatory assignments were made</li>\n";
        echo "<li>There's an issue with the form distribution logic</li>\n";
        echo "<li>The clearance forms were created manually</li>\n";
        echo "</ul>\n";
        echo "<p><strong>Solution:</strong> Re-run form distribution or manually assign signatories to existing forms.</p>\n";
    }
    
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
