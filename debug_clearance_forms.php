<?php
require_once 'includes/config/database.php';

$db = Database::getInstance();
$connection = $db->getConnection();

echo "=== CLEARANCE FORM DISTRIBUTION DEBUG ===\n\n";

// 1. Check current clearance periods
echo "1. CURRENT CLEARANCE PERIODS:\n";
$stmt = $connection->query("
    SELECT 
        p.period_id,
        p.sector,
        p.status,
        p.start_date,
        p.end_date,
        s.semester_name,
        ay.year as school_year
    FROM clearance_periods p
    JOIN semesters s ON p.semester_id = s.semester_id
    JOIN academic_years ay ON s.academic_year_id = ay.academic_year_id
    WHERE ay.is_active = 1
    ORDER BY p.sector, p.created_at DESC
");
$periods = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($periods as $period) {
    echo "   - {$period['sector']}: {$period['status']} (Period ID: {$period['period_id']})\n";
    echo "     School Year: {$period['school_year']}, Semester: {$period['semester_name']}\n";
    echo "     Start: {$period['start_date']}, End: {$period['end_date']}\n\n";
}

// 2. Check clearance forms by sector
echo "2. CLEARANCE FORMS BY SECTOR:\n";
$sectors = ['College', 'Senior High School', 'Faculty'];

foreach ($sectors as $sector) {
    $stmt = $connection->prepare("
        SELECT 
            COUNT(*) as total_forms,
            COUNT(CASE WHEN status = 'Unapplied' THEN 1 END) as unapplied,
            COUNT(CASE WHEN status = 'Applied' THEN 1 END) as applied,
            COUNT(CASE WHEN status = 'Completed' THEN 1 END) as completed,
            COUNT(CASE WHEN status = 'Rejected' THEN 1 END) as rejected
        FROM clearance_forms cf
        JOIN semesters s ON cf.semester_id = s.semester_id
        JOIN academic_years ay ON s.academic_year_id = ay.academic_year_id
        WHERE cf.clearance_type = ? AND ay.is_active = 1
    ");
    $stmt->execute([$sector]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "   {$sector}:\n";
    echo "     Total Forms: {$stats['total_forms']}\n";
    echo "     Unapplied: {$stats['unapplied']}\n";
    echo "     Applied: {$stats['applied']}\n";
    echo "     Completed: {$stats['completed']}\n";
    echo "     Rejected: {$stats['rejected']}\n\n";
}

// 3. Check signatory assignments by sector
echo "3. SIGNATORY ASSIGNMENTS BY SECTOR:\n";
foreach ($sectors as $sector) {
    $stmt = $connection->prepare("
        SELECT 
            COUNT(*) as total_assignments,
            COUNT(DISTINCT user_id) as unique_users,
            COUNT(DISTINCT designation_id) as unique_designations
        FROM sector_signatory_assignments 
        WHERE clearance_type = ? AND is_active = 1
    ");
    $stmt->execute([$sector]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "   {$sector}:\n";
    echo "     Total Assignments: {$stats['total_assignments']}\n";
    echo "     Unique Users: {$stats['unique_users']}\n";
    echo "     Unique Designations: {$stats['unique_designations']}\n\n";
}

// 4. Check clearance signatories (form-level signatories)
echo "4. CLEARANCE SIGNATORIES (FORM-LEVEL):\n";
foreach ($sectors as $sector) {
    $stmt = $connection->prepare("
        SELECT 
            COUNT(*) as total_signatories,
            COUNT(CASE WHEN action = 'Unapplied' THEN 1 END) as unapplied,
            COUNT(CASE WHEN action = 'Pending' THEN 1 END) as pending,
            COUNT(CASE WHEN action = 'Approved' THEN 1 END) as approved,
            COUNT(CASE WHEN action = 'Rejected' THEN 1 END) as rejected
        FROM clearance_signatories cs
        JOIN clearance_forms cf ON cs.clearance_form_id = cf.clearance_form_id
        JOIN semesters s ON cf.semester_id = s.semester_id
        JOIN academic_years ay ON s.academic_year_id = ay.academic_year_id
        WHERE cf.clearance_type = ? AND ay.is_active = 1
    ");
    $stmt->execute([$sector]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "   {$sector}:\n";
    echo "     Total Signatories: {$stats['total_signatories']}\n";
    echo "     Unapplied: {$stats['unapplied']}\n";
    echo "     Pending: {$stats['pending']}\n";
    echo "     Approved: {$stats['approved']}\n";
    echo "     Rejected: {$stats['rejected']}\n\n";
}

// 5. Check eligible users for each sector
echo "5. ELIGIBLE USERS BY SECTOR:\n";
foreach ($sectors as $sector) {
    switch ($sector) {
        case 'College':
            $stmt = $connection->query("
                SELECT COUNT(*) as count
                FROM users u
                INNER JOIN students s ON u.user_id = s.user_id
                INNER JOIN departments d ON s.department_id = d.department_id
                INNER JOIN sectors sec ON d.sector_id = sec.sector_id
                WHERE sec.sector_name = 'College'
                AND u.status = 'active'
                AND s.enrollment_status = 'Enrolled'
            ");
            break;
        case 'Senior High School':
            $stmt = $connection->query("
                SELECT COUNT(*) as count
                FROM users u
                INNER JOIN students s ON u.user_id = s.user_id
                INNER JOIN departments d ON s.department_id = d.department_id
                INNER JOIN sectors sec ON d.sector_id = sec.sector_id
                WHERE sec.sector_name = 'Senior High School'
                AND u.status = 'active'
                AND s.enrollment_status = 'Enrolled'
            ");
            break;
        case 'Faculty':
            $stmt = $connection->query("
                SELECT COUNT(*) as count
                FROM users u
                INNER JOIN faculty f ON u.user_id = f.user_id
                INNER JOIN departments d ON f.department_id = d.department_id
                INNER JOIN sectors sec ON d.sector_id = sec.sector_id
                WHERE sec.sector_name = 'Faculty'
                AND u.status = 'active'
            ");
            break;
    }
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   {$sector}: {$result['count']} eligible users\n";
}

echo "\n6. SAMPLE CLEARANCE FORMS:\n";
$stmt = $connection->query("
    SELECT 
        cf.clearance_form_id,
        cf.clearance_type,
        cf.status,
        u.first_name,
        u.last_name,
        COUNT(cs.signatory_id) as signatory_count
    FROM clearance_forms cf
    JOIN users u ON cf.user_id = u.user_id
    LEFT JOIN clearance_signatories cs ON cf.clearance_form_id = cs.clearance_form_id
    JOIN semesters s ON cf.semester_id = s.semester_id
    JOIN academic_years ay ON s.academic_year_id = ay.academic_year_id
    WHERE ay.is_active = 1
    GROUP BY cf.clearance_form_id
    ORDER BY cf.clearance_type, u.last_name
    LIMIT 10
");
$forms = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($forms as $form) {
    echo "   - {$form['clearance_form_id']}: {$form['first_name']} {$form['last_name']} ({$form['clearance_type']}) - {$form['status']} - {$form['signatory_count']} signatories\n";
}

echo "\n✅ Debug complete!\n";
?>
