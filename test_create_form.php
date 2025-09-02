<?php
// Quick CLI script to test the 5-digit clearance_form_id trigger
// Usage: php test_create_form.php

require_once __DIR__ . '/includes/config/database.php';

try {
    $db         = Database::getInstance();
    $connection = $db->getConnection();

    // 1) Fetch an active academic year & semester (fallback to first rows)
    $yearStmt = $connection->query("SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1");
    $yearRow  = $yearStmt->fetch(PDO::FETCH_ASSOC);
    if (!$yearRow) {
        throw new Exception('No active academic year found.');
    }
    $academicYearId = $yearRow['academic_year_id'];

    $semStmt = $connection->prepare("SELECT semester_id FROM semesters WHERE academic_year_id = ? AND is_active = 1 LIMIT 1");
    $semStmt->execute([$academicYearId]);
    $semRow = $semStmt->fetch(PDO::FETCH_ASSOC);
    if (!$semRow) {
        throw new Exception('No active semester found for the active academic year.');
    }
    $semesterId = $semRow['semester_id'];

    // 2) Get admin user (as test user)
    $userStmt = $connection->prepare("SELECT user_id FROM users WHERE username = 'admin' LIMIT 1");
    $userStmt->execute();
    $userRow = $userStmt->fetch(PDO::FETCH_ASSOC);
    if (!$userRow) {
        throw new Exception('Admin user not found.');
    }
    $userId = $userRow['user_id'];

    // 3) Insert into clearance_forms — let the trigger generate the ID
    $insert = $connection->prepare(
        "INSERT INTO clearance_forms (user_id, academic_year_id, semester_id, clearance_type) VALUES (?, ?, ?, 'Student')"
    );
    $insert->execute([$userId, $academicYearId, $semesterId]);

    // 4) Retrieve the generated clearance_form_id
    $formIdStmt = $connection->query("SELECT clearance_form_id FROM clearance_forms ORDER BY created_at DESC LIMIT 1");
    $formRow    = $formIdStmt->fetch(PDO::FETCH_ASSOC);

    if ($formRow) {
        echo "✅ Clearance form created with ID: " . $formRow['clearance_form_id'] . PHP_EOL;
    } else {
        echo "⚠️   Form inserted but could not retrieve ID." . PHP_EOL;
    }

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
