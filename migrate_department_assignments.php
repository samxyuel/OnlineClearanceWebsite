<?php
// migrate_department_assignments.php

require_once __DIR__ . '/includes/config/database.php';

echo "<h1>Department Assignment Migration</h1>";

try {
    $pdo = Database::getInstance()->getConnection();
    $pdo->beginTransaction();

    echo "<p><b>Phase 1: Migrating from 'staff' table...</b></p>";
    $staffStmt = $pdo->query("
        SELECT user_id, department_id 
        FROM staff 
        WHERE department_id IS NOT NULL AND user_id IS NOT NULL
    ");
    $staffMembers = $staffStmt->fetchAll(PDO::FETCH_ASSOC);

    $migratedCount = 0;
    $skippedCount = 0;

    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM user_department_assignments WHERE user_id = ? AND department_id = ?");
    $insertStmt = $pdo->prepare("INSERT INTO user_department_assignments (user_id, department_id, is_active) VALUES (?, ?, 1)");

    foreach ($staffMembers as $staff) {
        $userId = $staff['user_id'];
        $departmentId = $staff['department_id'];

        if (!$userId || !$departmentId) continue;

        $checkStmt->execute([$userId, $departmentId]);
        $exists = (int)$checkStmt->fetchColumn();

        if ($exists) {
            echo "<p style='color:orange;'>Skipping Staff User ID $userId, Department ID $departmentId: Assignment already exists.</p>";
            $skippedCount++;
        } else {
            $insertStmt->execute([$userId, $departmentId]);
            echo "<p style='color:green;'>Migrated Staff User ID $userId to Department ID $departmentId.</p>";
            $migratedCount++;
        }
    }

    echo "<p><b>Phase 2: Migrating from 'faculty' table...</b></p>";
    $facultyStmt = $pdo->query("
        SELECT user_id, department_id 
        FROM faculty 
        WHERE department_id IS NOT NULL AND user_id IS NOT NULL
    ");
    $facultyMembers = $facultyStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($facultyMembers as $faculty) {
        $userId = $faculty['user_id'];
        $departmentId = $faculty['department_id'];

        if (!$userId || !$departmentId) continue;

        $checkStmt->execute([$userId, $departmentId]);
        $exists = (int)$checkStmt->fetchColumn();

        if ($exists) {
            echo "<p style='color:orange;'>Skipping Faculty User ID $userId, Department ID $departmentId: Assignment already exists.</p>";
            $skippedCount++;
        } else {
            $insertStmt->execute([$userId, $departmentId]);
            echo "<p style='color:green;'>Migrated Faculty User ID $userId to Department ID $departmentId.</p>";
            $migratedCount++;
        }
    }


    $pdo->commit();

    echo "<hr>";
    echo "<h2>Migration Complete</h2>";
    echo "<p><b>Migrated:</b> $migratedCount users</p>";
    echo "<p><b>Skipped:</b> $skippedCount users</p>";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<h2>An error occurred:</h2>";
    echo "<p style='color:red;'>" . $e->getMessage() . "</p>";
}
?>
