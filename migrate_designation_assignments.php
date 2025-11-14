<?php
// migrate_designation_assignments.php

require_once __DIR__ . '/includes/config/database.php';

echo "<h1>Designation Assignment Migration</h1>";

try {
    $pdo = Database::getInstance()->getConnection();
    $pdo->beginTransaction();

    echo "<p>Starting migration...</p>";

    // Find all users in the staff table with a designation_id
    $staffStmt = $pdo->query("
        SELECT user_id, designation_id 
        FROM staff 
        WHERE designation_id IS NOT NULL AND user_id IS NOT NULL
    ");
    $staffMembers = $staffStmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($staffMembers)) {
        echo "<p>No staff members with designation IDs found in the 'staff' table. Nothing to migrate.</p>";
        $pdo->rollBack();
        exit;
    }

    $migratedCount = 0;
    $skippedCount = 0;

    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM user_designation_assignments WHERE user_id = ? AND designation_id = ?");
    $insertStmt = $pdo->prepare("INSERT INTO user_designation_assignments (user_id, designation_id, is_active) VALUES (?, ?, 1)");

    foreach ($staffMembers as $staff) {
        $userId = $staff['user_id'];
        $designationId = $staff['designation_id'];

        // Check if an assignment already exists
        $checkStmt->execute([$userId, $designationId]);
        $exists = (int)$checkStmt->fetchColumn();

        if ($exists) {
            echo "<p style='color:orange;'>Skipping User ID $userId, Designation ID $designationId: Assignment already exists.</p>";
            $skippedCount++;
        } else {
            // Insert the new assignment
            $insertStmt->execute([$userId, $designationId]);
            echo "<p style='color:green;'>Migrated User ID $userId to Designation ID $designationId.</p>";
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
