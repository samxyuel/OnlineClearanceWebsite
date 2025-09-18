<?php
// Create test clearance period for testing
require_once 'includes/config/database.php';

echo "Creating test clearance period...\n";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // First, check if we have academic years and semesters
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM academic_years WHERE is_active = 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Active academic years: " . $result['count'] . "\n";
    
    if ($result['count'] == 0) {
        // Create an active academic year
        $stmt = $pdo->prepare("INSERT INTO academic_years (year, is_active) VALUES (?, 1)");
        $stmt->execute(['2024-2025']);
        $academicYearId = $pdo->lastInsertId();
        echo "Created academic year: $academicYearId\n";
    } else {
        $stmt = $pdo->query("SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1");
        $academicYearId = $stmt->fetchColumn();
        echo "Using existing academic year: $academicYearId\n";
    }
    
    // Check for semesters
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM semesters WHERE academic_year_id = ?");
    $stmt->execute([$academicYearId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Semesters for this year: " . $result['count'] . "\n";
    
    if ($result['count'] == 0) {
        // Create semesters
        $semesters = ['1st', '2nd'];
        
        foreach ($semesters as $sem) {
            $stmt = $pdo->prepare("INSERT INTO semesters (academic_year_id, semester_name, is_active) VALUES (?, ?, 1)");
            $stmt->execute([$academicYearId, $sem]);
            $semesterId = $pdo->lastInsertId();
            echo "Created semester: $sem (ID: $semesterId)\n";
        }
    }
    
    // Get the first semester
    $stmt = $pdo->prepare("SELECT semester_id FROM semesters WHERE academic_year_id = ? ORDER BY semester_id LIMIT 1");
    $stmt->execute([$academicYearId]);
    $semesterId = $stmt->fetchColumn();
    
    if ($semesterId) {
        // Create an active clearance period
        $stmt = $pdo->prepare("INSERT INTO clearance_periods (semester_id, start_date, end_date, status) VALUES (?, ?, ?, 'active')");
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime('+30 days'));
        $stmt->execute([$semesterId, $startDate, $endDate]);
        $periodId = $pdo->lastInsertId();
        echo "Created active clearance period: $periodId\n";
        echo "Start date: $startDate\n";
        echo "End date: $endDate\n";
    }
    
    echo "✅ Test clearance period created successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
