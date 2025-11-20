<?php
/**
 * API: School Administrator Dashboard Summary
 *
 * Provides comprehensive school-wide statistics for the School Administrator dashboard,
 * including sector-based clearance statistics and school-wide metrics.
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

$pdo = Database::getInstance()->getConnection();

try {
    $summaryData = [
        'user' => [
            'first_name' => $auth->getCurrentUser()['first_name'] ?? null,
            'last_name' => $auth->getCurrentUser()['last_name'] ?? null
        ],
        'total_students' => 0,
        'total_faculty' => 0,
        'completed_clearances' => [
            'student' => 0,
            'faculty' => 0
        ],
        'pending_signatures' => 0,
        'sector_stats' => [
            'college' => ['applied' => 0, 'completed' => 0],
            'shs' => ['applied' => 0, 'completed' => 0],
            'faculty' => ['applied' => 0, 'completed' => 0]
        ]
    ];

    // Get total students and faculty
    $totalStudentsStmt = $pdo->query("SELECT COUNT(*) FROM students");
    $summaryData['total_students'] = (int)$totalStudentsStmt->fetchColumn();

    $totalFacultyStmt = $pdo->query("SELECT COUNT(*) FROM faculty");
    $summaryData['total_faculty'] = (int)$totalFacultyStmt->fetchColumn();

    // Get active academic year and semester
    $activeTermStmt = $pdo->prepare("
        SELECT ay.academic_year_id, s.semester_id
        FROM semesters s
        JOIN academic_years ay ON s.academic_year_id = ay.academic_year_id
        WHERE s.is_active = 1 LIMIT 1
    ");
    $activeTermStmt->execute();
    $activeTerm = $activeTermStmt->fetch(PDO::FETCH_ASSOC);

    if ($activeTerm) {
        $academicYearId = $activeTerm['academic_year_id'];
        $semesterId = $activeTerm['semester_id'];

        // Get sector statistics
        $sectorStatsSql = "
            SELECT 
                cf.clearance_type,
                COUNT(DISTINCT cf.user_id) as applied_count,
                COUNT(DISTINCT CASE WHEN cf.clearance_form_progress = 'Completed' THEN cf.user_id END) as completed_count
            FROM clearance_forms cf
            WHERE cf.academic_year_id = ? 
              AND cf.semester_id = ? 
              AND cf.clearance_type IN ('College', 'Senior High School', 'Faculty')
            GROUP BY cf.clearance_type
        ";
        $sectorStatsStmt = $pdo->prepare($sectorStatsSql);
        $sectorStatsStmt->execute([$academicYearId, $semesterId]);
        $sectorResults = $sectorStatsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($sectorResults as $row) {
            if ($row['clearance_type'] === 'College') {
                $summaryData['sector_stats']['college']['applied'] = (int)$row['applied_count'];
                $summaryData['sector_stats']['college']['completed'] = (int)$row['completed_count'];
            } elseif ($row['clearance_type'] === 'Senior High School') {
                $summaryData['sector_stats']['shs']['applied'] = (int)$row['applied_count'];
                $summaryData['sector_stats']['shs']['completed'] = (int)$row['completed_count'];
            } elseif ($row['clearance_type'] === 'Faculty') {
                $summaryData['sector_stats']['faculty']['applied'] = (int)$row['applied_count'];
                $summaryData['sector_stats']['faculty']['completed'] = (int)$row['completed_count'];
            }
        }

        // Calculate completed clearances
        $summaryData['completed_clearances']['student'] = 
            $summaryData['sector_stats']['college']['completed'] + 
            $summaryData['sector_stats']['shs']['completed'];
        $summaryData['completed_clearances']['faculty'] = 
            $summaryData['sector_stats']['faculty']['completed'];

        // Get pending signatures count
        $pendingSignaturesStmt = $pdo->prepare("
            SELECT COUNT(DISTINCT cs.signatory_id) as pending_count
            FROM clearance_signatories cs
            JOIN clearance_forms cf ON cs.clearance_form_id = cf.clearance_form_id
            WHERE cs.action = 'Pending'
              AND cf.academic_year_id = ? 
              AND cf.semester_id = ?
        ");
        $pendingSignaturesStmt->execute([$academicYearId, $semesterId]);
        $summaryData['pending_signatures'] = (int)$pendingSignaturesStmt->fetchColumn();
    }

    echo json_encode(['success' => true, 'data' => $summaryData]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
