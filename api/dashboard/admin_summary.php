<?php
/**
 * Admin Dashboard Summary API
 * 
 * Provides comprehensive statistics for the admin dashboard including:
 * - Current academic year and term information
 * - Sector-based clearance statistics
 * - Overall system metrics
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/config/database.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

try {
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required.']);
        exit;
    }

    // Check if user has admin role
    $userRole = strtolower($_SESSION['role_name'] ?? '');
    if ($userRole !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin access required.']);
        exit;
    }

    $pdo = Database::getInstance()->getConnection();

    // 1. Get current academic year and active term
    $academicYearStmt = $pdo->query("
        SELECT ay.academic_year_id, ay.year as academic_year
        FROM academic_years ay
        WHERE ay.is_active = 1
        LIMIT 1
    ");
    $academicYear = $academicYearStmt->fetch(PDO::FETCH_ASSOC);

    $activeTermStmt = $pdo->query("
        SELECT s.semester_id, s.semester_name, s.created_at
        FROM semesters s
        WHERE s.is_active = 1
        LIMIT 1
    ");
    $activeTerm = $activeTermStmt->fetch(PDO::FETCH_ASSOC);

    // 2. Get sector-based clearance statistics
    $sectors = ['College', 'Senior High School', 'Faculty'];
    $sectorStats = [];

    foreach ($sectors as $sector) {
        // Get clearance forms for this sector
        $formsStmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_forms,
                SUM(CASE WHEN cf.clearance_form_progress = 'Completed' THEN 1 ELSE 0 END) as completed_forms,
                SUM(CASE WHEN cf.clearance_form_progress = 'In Progress' THEN 1 ELSE 0 END) as in_progress_forms,
                SUM(CASE WHEN cf.clearance_form_progress = 'Not Started' THEN 1 ELSE 0 END) as not_started_forms
            FROM clearance_forms cf
            WHERE cf.clearance_type = ?
        ");
        $formsStmt->execute([$sector]);
        $formsData = $formsStmt->fetch(PDO::FETCH_ASSOC);

        $sectorStats[$sector] = [
            'applied' => (int)$formsData['total_forms'],
            'completed' => (int)$formsData['completed_forms'],
            'in_progress' => (int)$formsData['in_progress_forms'],
            'not_started' => (int)$formsData['not_started_forms']
        ];
    }

    // 3. Get overall system statistics
    $totalUsersStmt = $pdo->query("
        SELECT 
            (SELECT COUNT(*) FROM students) as total_students,
            (SELECT COUNT(*) FROM faculty) as total_faculty,
            (SELECT COUNT(*) FROM staff) as total_staff
    ");
    $totalUsers = $totalUsersStmt->fetch(PDO::FETCH_ASSOC);

    // 3a. Get active clearances count (clearance forms that are in progress or not started)
    $activeClearancesStmt = $pdo->query("
        SELECT COUNT(*) as active_clearances
        FROM clearance_forms
        WHERE clearance_form_progress IN ('In Progress', 'Not Started')
    ");
    $activeClearances = $activeClearancesStmt->fetch(PDO::FETCH_ASSOC);
    $totalUsers['active_clearances'] = (int)$activeClearances['active_clearances'];

    // 4. Get recent activity (last 10 activities)
    $recentActivityStmt = $pdo->query("
        SELECT 
            'clearance_completed' as activity_type,
            CONCAT(u.first_name, ' ', u.last_name) as user_name,
            cf.clearance_type,
            cf.updated_at as activity_time
        FROM clearance_forms cf
        JOIN users u ON cf.user_id = u.user_id
        WHERE cf.clearance_form_progress = 'Completed'
        ORDER BY cf.updated_at DESC
        LIMIT 10
    ");
    $recentActivity = $recentActivityStmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Get active clearance periods by sector
    $periodsStmt = $pdo->query("
        SELECT 
            cp.sector,
            cp.status,
            cp.start_date,
            cp.end_date,
            cp.created_at
        FROM clearance_periods cp
        WHERE cp.status IN ('Ongoing', 'Paused')
        ORDER BY cp.created_at DESC
    ");
    $activePeriods = $periodsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Organize periods by sector
    $periodsBySector = [];
    foreach ($activePeriods as $period) {
        $periodsBySector[$period['sector']][] = $period;
    }

    // Prepare response data
    $responseData = [
        'academic_year' => $academicYear,
        'active_term' => $activeTerm,
        'sector_stats' => $sectorStats,
        'total_users' => $totalUsers,
        'recent_activity' => $recentActivity,
        'active_periods' => $periodsBySector,
        'college' => $sectorStats['College'] ?? ['applied' => 0, 'completed' => 0],
        'shs' => $sectorStats['Senior High School'] ?? ['applied' => 0, 'completed' => 0],
        'faculty' => $sectorStats['Faculty'] ?? ['applied' => 0, 'completed' => 0]
    ];

    echo json_encode([
        'success' => true,
        'data' => $responseData,
        'message' => 'Admin dashboard data loaded successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load admin dashboard data: ' . $e->getMessage()
    ]);
}
?>
