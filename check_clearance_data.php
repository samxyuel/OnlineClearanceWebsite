<?php
require_once 'includes/config/database.php';

try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    echo "Checking clearance data...\n\n";
    
    // Check academic years
    echo "Academic Years:\n";
    $stmt = $connection->query('SELECT * FROM academic_years');
    $years = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($years)) {
        echo "❌ No academic years found\n";
    } else {
        foreach ($years as $year) {
            echo "- {$year['year']} (Active: " . ($year['is_active'] ? 'Yes' : 'No') . ")\n";
        }
    }
    
    // Check semesters
    echo "\nSemesters:\n";
    $stmt = $connection->query('SELECT * FROM semesters');
    $semesters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($semesters)) {
        echo "❌ No semesters found\n";
    } else {
        foreach ($semesters as $semester) {
            echo "- {$semester['semester_name']} (Active: " . ($semester['is_active'] ? 'Yes' : 'No') . ")\n";
        }
    }
    
    // Check clearance periods
    echo "\nClearance Periods:\n";
    $stmt = $connection->query('SELECT * FROM clearance_periods');
    $periods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($periods)) {
        echo "❌ No clearance periods found\n";
    } else {
        foreach ($periods as $period) {
            echo "- Period {$period['period_id']} (Active: " . ($period['is_active'] ? 'Yes' : 'No') . ")\n";
        }
    }
    
    // Check clearance requirements
    echo "\nClearance Requirements:\n";
    $stmt = $connection->query('SELECT * FROM clearance_requirements');
    $requirements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($requirements)) {
        echo "❌ No clearance requirements found\n";
    } else {
        foreach ($requirements as $req) {
            echo "- {$req['clearance_type']} requirement\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
