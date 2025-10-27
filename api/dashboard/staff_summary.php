<?php
/**
 * Staff Dashboard Summary API
 *
 * Provides key statistics for a logged-in staff member, including:
 * - Active clearance period information.
 * - Pending clearance counts for assigned sectors (Student, Faculty).
 * - Overall signing statistics for the staff member.
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

    $userId = $auth->getUserId();
    $pdo = Database::getInstance()->getConnection();

    // 1. Get the currently active clearance period (if any)
    $periodSql = "
        SELECT 
            p.period_name,
            p.start_date,
            ay.year as academic_year,
            s.semester_name
        FROM clearance_periods p
        JOIN semesters s ON p.semester_id = s.semester_id
        JOIN academic_years ay ON p.academic_year_id = ay.academic_year_id
        WHERE p.status = 'Ongoing'
        ORDER BY p.start_date DESC
        LIMIT 1
    ";
    $periodStmt = $pdo->query($periodSql);
    $activePeriod = $periodStmt->fetch(PDO::FETCH_ASSOC);

    // 2. Get the staff member's designation ID
    $staffSql = "SELECT designation_id FROM staff WHERE user_id = ? LIMIT 1";
    $staffStmt = $pdo->prepare($staffSql);
    $staffStmt->execute([$userId]);
    $designationId = $staffStmt->fetchColumn();

    if (!$designationId) {
        throw new Exception("Staff member has no designation assigned.");
    }

    // 3. Get pending counts for the staff member's designation
    $pendingCounts = [
        'student' => 0,
        'faculty' => 0,
        'total' => 0
    ];

    if ($activePeriod) {
        $pendingSql = "
            SELECT 
                cf.clearance_type,
                COUNT(cs.signatory_id) as pending_count
            FROM clearance_signatories cs
            JOIN clearance_forms cf ON cs.clearance_form_id = cf.clearance_form_id
            JOIN clearance_periods cp ON (cf.academic_year_id = cp.academic_year_id AND cf.semester_id = cp.semester_id AND cf.clearance_type = cp.sector)
            WHERE cs.designation_id = ?
              AND cs.action = 'Pending'
              AND cp.status = 'Ongoing'
            GROUP BY cf.clearance_type
        ";
        $pendingStmt = $pdo->prepare($pendingSql);
        $pendingStmt->execute([$designationId]);
        $results = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as $row) {
            if ($row['clearance_type'] === 'College' || $row['clearance_type'] === 'Senior High School') {
                $pendingCounts['student'] += (int)$row['pending_count'];
            } elseif ($row['clearance_type'] === 'Faculty') {
                $pendingCounts['faculty'] += (int)$row['pending_count'];
            }
        }
        $pendingCounts['total'] = $pendingCounts['student'] + $pendingCounts['faculty'];
    }

    // 4. Get overall signing stats for the staff member
    $statsSql = "
        SELECT 
            action,
            COUNT(signatory_id) as count
        FROM clearance_signatories
        WHERE actual_user_id = ?
        GROUP BY action
    ";
    $statsStmt = $pdo->prepare($statsSql);
    $statsStmt->execute([$userId]);
    $rawStats = $statsStmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $signingStats = [
        'approved' => (int)($rawStats['Approved'] ?? 0),
        'rejected' => (int)($rawStats['Rejected'] ?? 0),
        'total_signed' => (int)($rawStats['Approved'] ?? 0) + (int)($rawStats['Rejected'] ?? 0)
    ];

    // 5. Get sector statistics for the current period
    $sectorStats = [
        'college' => ['applied' => 0, 'completed' => 0],
        'shs' => ['applied' => 0, 'completed' => 0],
        'faculty' => ['applied' => 0, 'completed' => 0]
    ];

    if ($activePeriod) {
        $academicYearId = null;
        $semesterId = null;
        
        // Get academic year and semester IDs
        $periodInfoSql = "
            SELECT ay.academic_year_id, s.semester_id
            FROM clearance_periods p
            JOIN semesters s ON p.semester_id = s.semester_id
            JOIN academic_years ay ON p.academic_year_id = ay.academic_year_id
            WHERE p.status = 'Ongoing'
            ORDER BY p.start_date DESC
            LIMIT 1
        ";
        $periodInfoStmt = $pdo->query($periodInfoSql);
        $periodInfo = $periodInfoStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($periodInfo) {
            $academicYearId = $periodInfo['academic_year_id'];
            $semesterId = $periodInfo['semester_id'];
            
            // Get clearance stats for each sector
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
                    $sectorStats['college']['applied'] = (int)$row['applied_count'];
                    $sectorStats['college']['completed'] = (int)$row['completed_count'];
                } elseif ($row['clearance_type'] === 'Senior High School') {
                    $sectorStats['shs']['applied'] = (int)$row['applied_count'];
                    $sectorStats['shs']['completed'] = (int)$row['completed_count'];
                } elseif ($row['clearance_type'] === 'Faculty') {
                    $sectorStats['faculty']['applied'] = (int)$row['applied_count'];
                    $sectorStats['faculty']['completed'] = (int)$row['completed_count'];
                }
            }
        }
    }

    // 6. Combine and return the data
    $response = [
        'success' => true,
        'data' => [
            'active_period' => $activePeriod ? [
                'name' => $activePeriod['period_name'],
                'academic_year' => $activePeriod['academic_year'],
                'semester_name' => $activePeriod['semester_name'],
                'start_date' => $activePeriod['start_date']
            ] : null,
            'pending_clearances' => $pendingCounts,
            'signing_stats' => $signingStats,
            'sector_stats' => $sectorStats
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server Error: ' . $e->getMessage()
    ]);
}
?>