<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../includes/config/database.php';
require_once '../../includes/classes/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

try {
    $db = Database::getInstance();
    $connection = $db->getConnection();

    $academicYearId = isset($_GET['academic_year_id']) ? (int)$_GET['academic_year_id'] : 0;
    $semesterId     = isset($_GET['semester_id']) ? (int)$_GET['semester_id'] : 0;

    if ($academicYearId <= 0 || $semesterId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'academic_year_id and semester_id are required']);
        exit;
    }

    $issues = [];

    // Validate academic year exists and is current/active
    $stmt = $connection->prepare("SELECT academic_year_id, year, is_active FROM academic_years WHERE academic_year_id = ?");
    $stmt->execute([$academicYearId]);
    $ay = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$ay) {
        $issues[] = [
            'code' => 'AY_NOT_FOUND',
            'audience' => 'global',
            'message' => 'School Year not found.'
        ];
    } else if ((int)$ay['is_active'] !== 1) {
        $issues[] = [
            'code' => 'AY_NOT_CURRENT',
            'audience' => 'global',
            'message' => 'Only the current School Year can be activated.'
        ];
    }

    // Validate semester belongs to this academic year
    $stmt = $connection->prepare("SELECT semester_id, semester_name FROM semesters WHERE semester_id = ? AND academic_year_id = ?");
    $stmt->execute([$semesterId, $academicYearId]);
    $sem = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$sem) {
        $issues[] = [
            'code' => 'SEM_NOT_FOUND',
            'audience' => 'global',
            'message' => 'Semester not found in this School Year.'
        ];
    }

    // Determine if we are reactivating the same term or activating a different term
    $stmt = $connection->prepare("SELECT status FROM clearance_periods WHERE academic_year_id = ? AND semester_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$academicYearId, $semesterId]);
    $targetPeriod = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($targetPeriod && strtolower($targetPeriod['status']) === 'deactivated') {
        // Reactivating the same deactivated term is allowed if no other term is active
        $stmt = $connection->prepare("SELECT COUNT(*) FROM clearance_periods WHERE academic_year_id = ? AND semester_id <> ? AND status = 'active'");
        $stmt->execute([$academicYearId, $semesterId]);
        if ((int)$stmt->fetchColumn() > 0) {
            $issues[] = [
                'code' => 'ANOTHER_ACTIVE',
                'audience' => 'global',
                'message' => 'Another term is currently active. End or deactivate it first.'
            ];
        }
    } else {
        // Activating a new/different term: block if any other term is active or deactivated (but not ended)
        $stmt = $connection->prepare("SELECT COUNT(*) FROM clearance_periods WHERE academic_year_id = ? AND semester_id <> ? AND status IN ('active','deactivated') AND ended_at IS NULL");
        $stmt->execute([$academicYearId, $semesterId]);
        if ((int)$stmt->fetchColumn() > 0) {
            $issues[] = [
                'code' => 'OTHER_TERM_NOT_ENDED',
                'audience' => 'global',
                'message' => 'Another term in this School Year is not Ended. End it first before activating this term.'
            ];
        }
    }

    // Enforce Term 2 sequencing: Term 2 can activate only if Term 1 is Ended
    if ($sem && in_array(strtolower($sem['semester_name']), ['2nd','second','term 2'])) {
        // Find the corresponding Term 1 semester in this AY
        $stmt = $connection->prepare("SELECT semester_id FROM semesters WHERE academic_year_id = ? AND (LOWER(semester_name) IN ('1st','first','term 1')) LIMIT 1");
        $stmt->execute([$academicYearId]);
        $sem1 = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$sem1) {
            $issues[] = [
                'code' => 'TERM1_MISSING',
                'audience' => 'global',
                'message' => 'Term 1 is missing for this School Year.'
            ];
        } else {
            // Check period status for Term 1 - check for 'ended' status OR 'ended_at' timestamp
            $stmt = $connection->prepare("SELECT status, ended_at FROM clearance_periods WHERE academic_year_id = ? AND semester_id = ? ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$academicYearId, $sem1['semester_id']]);
            $p1 = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Term 1 is considered ended if status is 'ended' OR if ended_at timestamp exists
            $isTerm1Ended = $p1 && (
                strtolower($p1['status']) === 'ended' || 
                !empty($p1['ended_at'])
            );
            
            if (!$p1 || !$isTerm1Ended) {
                $issues[] = [
                    'code' => 'TERM_SEQUENCE',
                    'audience' => 'global',
                    'message' => 'Term 1 must be Ended before activating Term 2.'
                ];
            }
        }
    }

    // Placeholder: Signatory configuration checks can be added in Phase 2

    $ok = empty($issues);
    echo json_encode([
        'success' => true,
        'ok' => $ok,
        'issues' => $issues
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>


