<?php
// assign_random_department.php

require_once __DIR__ . '/includes/config/database.php';

echo "<h1>Assigning Random Additional Department</h1>";

try {
    $pdo = Database::getInstance()->getConnection();
    $pdo->beginTransaction();

    $sourceDepartmentId = 50;
    $randomDepartmentPool = [44, 45, 46];

    echo "<p>Finding faculty users in Department ID: $sourceDepartmentId</p>";
    echo "<p>Random pool for additional assignment: " . implode(', ', $randomDepartmentPool) . "</p><hr>";

    // 1. Find all users in the source department from the faculty table
    $facultyStmt = $pdo->prepare("
        SELECT user_id 
        FROM faculty 
        WHERE department_id = ? AND user_id IS NOT NULL
    ");
    $facultyStmt->execute([$sourceDepartmentId]);
    $facultyUserIds = $facultyStmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($facultyUserIds)) {
        echo "<p>No faculty users found in Department $sourceDepartmentId. Nothing to do.</p>";
        $pdo->rollBack();
        exit;
    }

    $assignedCount = 0;
    $skippedCount = 0;

    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM user_department_assignments WHERE user_id = ? AND department_id = ?");
    $insertStmt = $pdo->prepare("INSERT INTO user_department_assignments (user_id, department_id, is_active) VALUES (?, ?, 1)");

    foreach ($facultyUserIds as $userId) {
        // 2. Pick a random department from the pool
        $randomDeptId = $randomDepartmentPool[array_rand($randomDepartmentPool)];

        // 3. Check if the user already has this random assignment
        $checkStmt->execute([$userId, $randomDeptId]);
        $exists = (int)$checkStmt->fetchColumn();

        if ($exists) {
            echo "<p style='color:orange;'>Skipping User ID $userId: Already has an assignment for randomly selected Department ID $randomDeptId.</p>";
            $skippedCount++;
        } else {
            // 4. Insert the new random assignment
            $insertStmt->execute([$userId, $randomDeptId]);
            echo "<p style='color:green;'>Assigned additional Department ID $randomDeptId to User ID $userId.</p>";
            $assignedCount++;
        }
    }

    $pdo->commit();

    echo "<hr>";
    echo "<h2>Operation Complete</h2>";
    echo "<p><b>Assigned additional departments to:</b> $assignedCount users</p>";
    echo "<p><b>Skipped:</b> $skippedCount users</p>";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<h2>An error occurred:</h2>";
    echo "<p style='color:red;'>" . $e->getMessage() . "</p>";
}
?>
