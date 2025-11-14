<?php
// debug_user_assignments.php

require_once __DIR__ . '/includes/config/database.php';

$userId = null;
if (isset($_GET['user_id'])) {
    $userId = (int)$_GET['user_id'];
}

if (!$userId) {
    echo "<h1>User Assignment Debugger</h1>";
    echo "<p>Please provide a user_id in the URL. Example: ?user_id=179</p>";
    exit;
}

echo "<h1>Debugging Assignments for User ID: $userId</h1>";

try {
    $pdo = Database::getInstance()->getConnection();

    // --- Check Designation Assignment ---
    echo "<h2>1. Designation Assignment</h2>";
    $desigStmt = $pdo->prepare("
        SELECT udaa.designation_id, d.designation_name, udaa.is_active
        FROM user_designation_assignments udaa
        JOIN designations d ON udaa.designation_id = d.designation_id
        WHERE udaa.user_id = ? AND d.designation_name = 'Program Head'
    ");
    $desigStmt->execute([$userId]);
    $designation = $desigStmt->fetch(PDO::FETCH_ASSOC);

    if (!$designation) {
        echo "<p style='color:red;'><b>Error:</b> No 'Program Head' designation assignment found for this user.</p>";
        $phIdStmt = $pdo->query("SELECT designation_id FROM designations WHERE designation_name = 'Program Head' LIMIT 1");
        $programHeadId = $phIdStmt->fetchColumn();
        if ($programHeadId) {
            echo "<b>To fix, run this SQL:</b>";
            echo "<pre>INSERT INTO user_designation_assignments (user_id, designation_id, is_active) VALUES ($userId, $programHeadId, 1);</pre>";
        } else {
            echo "<p style='color:red;'><b>CRITICAL:</b> 'Program Head' designation not found in the designations table.</p>";
        }
    } elseif ($designation['is_active'] != 1) {
        echo "<p style='color:orange;'><b>Warning:</b> 'Program Head' designation is assigned but inactive.</p>";
        echo "<b>To fix, run this SQL:</b>";
        echo "<pre>UPDATE user_designation_assignments SET is_active = 1 WHERE user_id = $userId AND designation_id = " . $designation['designation_id'] . ";</pre>";
    } else {
        echo "<p style='color:green;'><b>OK:</b> User has an active 'Program Head' designation assignment.</p>";
    }

    // --- Check Department Assignment ---
    echo "<h2>2. Department Assignment</h2>";
    $deptStmt = $pdo->prepare("
        SELECT department_id, is_active
        FROM user_department_assignments
        WHERE user_id = ?
    ");
    $deptStmt->execute([$userId]);
    $departments = $deptStmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($departments)) {
        echo "<p style='color:red;'><b>Error:</b> No department assignments found for this user.</p>";
        echo "<p>You need to assign a department to this user. Find a department_id from the 'departments' table.</p>";
        echo "<b>To fix (example with department_id = 1), run this SQL:</b>";
        echo "<pre>INSERT INTO user_department_assignments (user_id, department_id, is_active) VALUES ($userId, 1, 1);</pre>";
    } else {
        $activeDepts = array_filter($departments, function($dept) {
            return $dept['is_active'] == 1;
        });

        if (empty($activeDepts)) {
            echo "<p style='color:orange;'><b>Warning:</b> User has department assignments, but none are active.</p>";
            $inactiveIds = array_column($departments, 'department_id');
            echo "<b>To fix, run this SQL (activates all assigned departments):</b>";
            echo "<pre>UPDATE user_department_assignments SET is_active = 1 WHERE user_id = $userId AND department_id IN (" . implode(',', $inactiveIds) . ");</pre>";
        } else {
            echo "<p style='color:green;'><b>OK:</b> User has active department assignments.</p>";
            echo "<ul>";
            foreach ($activeDepts as $dept) {
                echo "<li>Active Department ID: " . $dept['department_id'] . "</li>";
            }
            echo "</ul>";
        }
    }

    echo "<hr>";
    echo "<h2>3. Final Check</h2>";
    if ($designation && $designation['is_active'] == 1 && !empty($activeDepts)) {
        echo "<p style='color:green;'><b>Conclusion:</b> Based on this check, the user SHOULD have access. If you are still getting 'Access Denied', the problem is elsewhere.</p>";
    } else {
        echo "<p style='color:red;'><b>Conclusion:</b> The user is missing the required active assignments. Please run the SQL commands above to fix the data.</p>";
    }

} catch (Exception $e) {
    echo "<h2>An error occurred:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
