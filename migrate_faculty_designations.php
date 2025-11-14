<?php
// migrate_faculty_designations.php

require_once __DIR__ . '/includes/config/database.php';

echo "<h1>Faculty Designation Assignment Migration</h1>";

try {
    $pdo = Database::getInstance()->getConnection();
    $pdo->beginTransaction();

    echo "<p>Starting migration...</p>";

    // 1. Get the 'Faculty' designation ID
    $facultyDesigStmt = $pdo->query("SELECT designation_id FROM designations WHERE designation_name = 'Faculty' LIMIT 1");
    $facultyDesignationId = $facultyDesigStmt->fetchColumn();

    if (!$facultyDesignationId) {
        echo "<p style='color:red;'><b>CRITICAL ERROR:</b> 'Faculty' designation not found in the 'designations' table. Cannot proceed.</p>";
        $pdo->rollBack();
        exit;
    }
    echo "<p>Found 'Faculty' designation with ID: $facultyDesignationId</p>";

    // 2. Find all users in the faculty table
    $facultyStmt = $pdo->query("SELECT user_id FROM faculty WHERE user_id IS NOT NULL");
    $facultyMembers = $facultyStmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($facultyMembers)) {
        echo "<p>No users found in the 'faculty' table. Nothing to migrate.</p>";
        $pdo->rollBack();
        exit;
    }

    $migratedCount = 0;
    $skippedCount = 0;

    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM user_designation_assignments WHERE user_id = ? AND designation_id = ?");
    $insertStmt = $pdo->prepare("INSERT INTO user_designation_assignments (user_id, designation_id, is_active) VALUES (?, ?, 1)");

    foreach ($facultyMembers as $faculty) {
        $userId = $faculty['user_id'];

        if (!$userId) continue;

        // 3. Check if this user already has the 'Faculty' designation
        $checkStmt->execute([$userId, $facultyDesignationId]);
        $exists = (int)$checkStmt->fetchColumn();

        if ($exists) {
            echo "<p style='color:orange;'>Skipping User ID $userId: 'Faculty' designation already assigned.</p>";
            $skippedCount++;
        } else {
            // 4. Insert the new assignment
            $insertStmt->execute([$userId, $facultyDesignationId]);
            echo "<p style='color:green;'>Assigned 'Faculty' designation to User ID $userId.</p>";
            $migratedCount++;
        }
    }

    $pdo->commit();

    echo "<hr>";
    echo "<h2>Migration Complete</h2>";
    echo "<p><b>Assigned 'Faculty' designation to:</b> $migratedCount users</p>";
    echo "<p><b>Skipped:</b> $skippedCount users</p>";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<h2>An error occurred:</h2>";
    echo "<p style='color:red;'>" . $e->getMessage() . "</p>";
}
?>
