<?php
/**
 * API: End-User Dashboard Summary
 * Provides a consolidated summary of data for the student/faculty dashboard.
 */

header('Content-Type: application/json');
require_once '../../includes/config/database.php';
require_once '../../includes/classes/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$userId = $auth->getUserId();
$userRole = strtolower($_SESSION['role_name'] ?? '');
$userSector = '';

$pdo = Database::getInstance()->getConnection();

try {
    // 1. Determine user's sector
    if ($userRole === 'student') {
        $stmt = $pdo->prepare("
            SELECT sec.sector_name as sector 
            FROM students s
            JOIN departments d ON s.department_id = d.department_id
            JOIN sectors sec ON d.department_type = sec.sector_name
            WHERE s.user_id = ?
        ");
    } else if ($userRole === 'faculty') {
        $stmt = $pdo->prepare("
            SELECT sec.sector_name as sector 
            FROM faculty f
            JOIN departments d ON f.department_id = d.department_id
            JOIN sectors sec ON d.department_type = sec.sector_name
            WHERE f.user_id = ?
        ");
    } else {
        // Fallback for other roles like admin, which don't have a sector
        $userSector = null;
    }

    if (isset($stmt)) {
        $stmt->execute([$userId]);
        $userSector = $stmt->fetchColumn();
    }

    // If a student or faculty user has no sector assigned, we cannot proceed.
    // For other roles (like Admin), it's normal to not have a sector.
    if (!$userSector) {
        // Instead of throwing an error, return a default empty state.
        // This is expected for roles like Admin who don't have a personal clearance dashboard.
        echo json_encode([
            'success' => true,
            'data' => [
                'period' => null,
                'clearance' => [
                    'status' => 'Not Applicable',
                    'progress_text' => 'N/A',
                    'approved_count' => 0,
                    'total_count' => 0
                ],
                'recent_activity' => []
            ]
        ]);
        exit;
    }

    // 2. Find the active clearance period for the user's sector
    // First, find the globally active academic year and semester
    $activeTermStmt = $pdo->prepare("
        SELECT ay.academic_year_id, ay.year as academic_year, s.semester_id, s.semester_name
        FROM semesters s
        JOIN academic_years ay ON s.academic_year_id = ay.academic_year_id
        WHERE s.is_active = 1 LIMIT 1
    ");
    $activeTermStmt->execute();
    $activeTerm = $activeTermStmt->fetch(PDO::FETCH_ASSOC);

    // Now, find the specific clearance period for the user's sector within that active term
    $periodStmt = $pdo->prepare("
        SELECT 
            cp.period_id, cp.start_date, cp.end_date, cp.status,
            cp.academic_year_id, cp.semester_id
        FROM clearance_periods cp
        WHERE cp.sector = ? AND cp.academic_year_id = ? AND cp.semester_id = ?
        ORDER BY cp.start_date DESC
        LIMIT 1
    ");
    $periodStmt->execute([$userSector, $activeTerm['academic_year_id'] ?? null, $activeTerm['semester_id'] ?? null]);
    $activePeriod = $periodStmt->fetch(PDO::FETCH_ASSOC);

    $dashboardData = [
        'period' => null,
        'clearance' => [
            'status' => 'Not Started',
            'progress_text' => '0/0 Done',
            'approved_count' => 0,
            'total_count' => 0
        ],
        'recent_activity' => []
    ];

    // A period is considered "active" for the dashboard if the term is active, even if the period status isn't 'Ongoing' yet.
    if ($activeTerm && $activePeriod) {
        $endDate = new DateTime($activePeriod['end_date']);
        $now = new DateTime();
        $interval = $now->diff($endDate);
        $daysRemaining = $interval->invert ? 0 : $interval->days;

        $dashboardData['period'] = [
            'academic_year' => $activeTerm['academic_year'],
            'semester_name' => $activeTerm['semester_name'],
            'days_remaining' => $daysRemaining
        ];

        // 3. Get user's clearance form and progress for this period
        $formStmt = $pdo->prepare("
            SELECT 
                cf.clearance_form_id,
                cf.clearance_form_progress as status,
                (SELECT COUNT(*) FROM clearance_signatories cs WHERE cs.clearance_form_id = cf.clearance_form_id) as total_signatories,
                (SELECT COUNT(*) FROM clearance_signatories cs WHERE cs.clearance_form_id = cf.clearance_form_id AND cs.action = 'Approved') as approved_signatories
            FROM clearance_forms cf
            WHERE cf.user_id = ? AND cf.academic_year_id = ? AND cf.semester_id = ? AND cf.clearance_type = ?
            LIMIT 1
        ");
        $formStmt->execute([$userId, $activePeriod['academic_year_id'], $activePeriod['semester_id'], $userSector]);
        $clearanceForm = $formStmt->fetch(PDO::FETCH_ASSOC);

        if ($clearanceForm) {
            $total = (int)$clearanceForm['total_signatories'];
            $approved = (int)$clearanceForm['approved_signatories'];
            $dashboardData['clearance'] = [
                'status' => ucfirst(str_replace('-', ' ', $clearanceForm['status'])),
                'progress_text' => "{$approved}/{$total} Done",
                'approved_count' => $approved,
                'total_count' => $total
            ];

            // 4. Get recent activity for this form
            $activityStmt = $pdo->prepare("
                SELECT 
                    cs.action, 
                    cs.date_signed,
                    d.designation_name
                FROM clearance_signatories cs
                JOIN designations d ON cs.designation_id = d.designation_id
                WHERE cs.clearance_form_id = ? AND cs.action != 'Pending'
                ORDER BY cs.date_signed DESC
                LIMIT 5
            ");
            $activityStmt->execute([$clearanceForm['clearance_form_id']]);
            $dashboardData['recent_activity'] = $activityStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    echo json_encode(['success' => true, 'data' => $dashboardData]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?>