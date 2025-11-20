<?php
/**
 * API: Program Head Dashboard Summary
 *
 * Fetches a comprehensive summary of data relevant to a logged-in Program Head,
 * scoped to their assigned departments.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/config/database.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

function send_json_response($success, $data = [], $message = '', $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit;
}

try {
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        send_json_response(false, [], 'Authentication required.', 401);
    }

    $userId = $auth->getUserId();
    $pdo = Database::getInstance()->getConnection();

    // 1. Get Program Head's assigned departments
    $deptStmt = $pdo->prepare("
        SELECT d.department_id, d.department_name 
        FROM staff s
        JOIN departments d ON s.department_id = d.department_id
        WHERE s.user_id = ? 
          AND s.staff_category = 'Program Head' 
          AND s.is_active = 1
    ");
    $deptStmt->execute([$userId]);
    $departments = $deptStmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($departments)) {
        send_json_response(false, [], 'No departments assigned to this Program Head.', 403);
    }
    $departmentIds = array_column($departments, 'department_id');

    // build placeholders and params for IN (...)
    $placeholders = implode(',', array_fill(0, count($departmentIds), '?'));

    // 2. Get User Info
    $user = $auth->getCurrentUser();

    // 3. Get Active Period
    $periodStmt = $pdo->query("
        SELECT
            cp.period_id,
            cp.academic_year_id,
            cp.semester_id,
            ay.year as academic_year, 
            s.semester_name, 
            cp.start_date, 
            cp.end_date 
        FROM clearance_periods cp
        JOIN academic_years ay ON cp.academic_year_id = ay.academic_year_id
        JOIN semesters s ON cp.semester_id = s.semester_id
        WHERE cp.status = 'Ongoing' LIMIT 1
    ");
    $activePeriod = $periodStmt->fetch(PDO::FETCH_ASSOC);

    // 4. Get Total Students and Faculty in assigned departments
    $totalStudentsStmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE department_id IN ($placeholders)");
    $totalStudentsStmt->execute($departmentIds);
    $totalStudents = $totalStudentsStmt->fetchColumn();

    $totalFacultyStmt = $pdo->prepare("SELECT COUNT(*) FROM faculty WHERE department_id IN ($placeholders)");
    $totalFacultyStmt->execute($departmentIds);
    $totalFaculty = $totalFacultyStmt->fetchColumn();

    // 5. Get Pending Signatures for this Program Head
    $pendingSignatures = ['student' => 0, 'faculty' => 0];
    if ($activePeriod) {
        $programHeadDesignationId = $pdo->query("SELECT designation_id FROM designations WHERE designation_name = 'Program Head' LIMIT 1")->fetchColumn();

        // If designation not found, treat as 0 / no results
        if (!$programHeadDesignationId) {
            $programHeadDesignationId = 0;
        }

        // Count pending student signatures
        $pendingStudentStmt = $pdo->prepare("
            SELECT COUNT(cs.signatory_id) AS cnt
            FROM clearance_signatories cs
            JOIN clearance_forms cf ON cs.clearance_form_id = cf.clearance_form_id
            JOIN students s ON cf.user_id = s.user_id
            WHERE cs.designation_id = ?
              AND s.department_id IN ($placeholders)
              AND cs.action = 'Pending'
              AND cf.academic_year_id = ? AND cf.semester_id = ?
              AND cf.clearance_type IN ('College', 'Senior High School')
        ");
        $studentParams = array_merge([$programHeadDesignationId], $departmentIds, [$activePeriod['academic_year_id'], $activePeriod['semester_id']]);
        $pendingStudentStmt->execute($studentParams);
        $pendingSignatures['student'] = (int)$pendingStudentStmt->fetchColumn();

        // Count pending faculty signatures
        $pendingFacultyStmt = $pdo->prepare("
            SELECT COUNT(cs.signatory_id) AS cnt
            FROM clearance_signatories cs
            JOIN clearance_forms cf ON cs.clearance_form_id = cf.clearance_form_id
            JOIN faculty f ON cf.user_id = f.user_id
            WHERE cs.designation_id = ?
              AND f.department_id IN ($placeholders)
              AND cs.action = 'Pending'
              AND cf.academic_year_id = ? AND cf.semester_id = ?
              AND cf.clearance_type = 'Faculty'
        ");
        $facultyParams = array_merge([$programHeadDesignationId], $departmentIds, [$activePeriod['academic_year_id'], $activePeriod['semester_id']]);
        $pendingFacultyStmt->execute($facultyParams);
        $pendingSignatures['faculty'] = (int)$pendingFacultyStmt->fetchColumn();
    }

    // 6. Get Overall Clearance Stats for the department
    $clearanceStats = [
        'student' => ['applied' => 0, 'completed' => 0, 'pending' => 0, 'rejected' => 0, 'in-progress' => 0],
        'faculty' => ['applied' => 0, 'completed' => 0, 'pending' => 0, 'rejected' => 0, 'in-progress' => 0]
    ];

    if ($activePeriod) { // Only calculate stats if a period is active
        // Student clearance stats (join to students to scope by department)
        $studentClearanceStmt = $pdo->prepare("
            SELECT cf.clearance_form_progress, COUNT(*) as count
            FROM clearance_forms cf
            JOIN students s ON cf.user_id = s.user_id
            WHERE s.department_id IN ($placeholders)
              AND cf.clearance_type IN ('College', 'Senior High School')
              AND cf.academic_year_id = ? AND cf.semester_id = ?
            GROUP BY cf.clearance_form_progress
        ");
        $studentClearanceStmt->execute(array_merge($departmentIds, [$activePeriod['academic_year_id'], $activePeriod['semester_id']]));
        $studentResults = $studentClearanceStmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $clearanceStats['student']['applied'] = array_sum($studentResults);
        $clearanceStats['student']['completed'] = $studentResults['complete'] ?? 0;
        $clearanceStats['student']['in-progress'] = $studentResults['in-progress'] ?? 0;

        // Faculty clearance stats (join to faculty)
        $facultyClearanceStmt = $pdo->prepare("
            SELECT cf.clearance_form_progress, COUNT(*) as count
            FROM clearance_forms cf
            JOIN faculty f ON cf.user_id = f.user_id
            WHERE f.department_id IN ($placeholders)
              AND cf.clearance_type = 'Faculty'
              AND cf.academic_year_id = ? AND cf.semester_id = ?
            GROUP BY cf.clearance_form_progress
        ");
        $facultyClearanceStmt->execute(array_merge($departmentIds, [$activePeriod['academic_year_id'], $activePeriod['semester_id']]));
        $facultyResults = $facultyClearanceStmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $clearanceStats['faculty']['applied'] = array_sum($facultyResults);
        $clearanceStats['faculty']['completed'] = $facultyResults['complete'] ?? 0;
        $clearanceStats['faculty']['in-progress'] = $facultyResults['in-progress'] ?? 0;

        // Get pending and rejected counts from signatories table for more accuracy
        // Use COALESCE of student.department_id and faculty.department_id to scope by department
        $signatoryCountsStmt = $pdo->prepare("
            SELECT
                cf.clearance_type,
                cs.action,
                COUNT(DISTINCT cs.clearance_form_id) as count
            FROM clearance_signatories cs
            JOIN clearance_forms cf ON cs.clearance_form_id = cf.clearance_form_id
            LEFT JOIN students s ON cf.user_id = s.user_id
            LEFT JOIN faculty f ON cf.user_id = f.user_id
            WHERE (COALESCE(s.department_id, f.department_id) IN ($placeholders))
              AND cs.action IN ('Pending', 'Rejected')
              AND cf.academic_year_id = ? AND cf.semester_id = ?
            GROUP BY cf.clearance_type, cs.action
        ");
        $signatoryCountsStmt->execute(array_merge($departmentIds, [$activePeriod['academic_year_id'], $activePeriod['semester_id']]));
        foreach ($signatoryCountsStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $type = in_array($row['clearance_type'], ['College', 'Senior High School']) ? 'student' : 'faculty';
            $status = strtolower($row['action']);
            // map status to fields we use
            if ($status === 'pending') {
                $clearanceStats[$type]['pending'] = max($clearanceStats[$type]['pending'], (int)$row['count']);
            } elseif ($status === 'rejected') {
                $clearanceStats[$type]['rejected'] = max($clearanceStats[$type]['rejected'], (int)$row['count']);
            }
        }
    }

    // 7. Get Program Stats
    $programStmt = $pdo->prepare("
        SELECT 
            p.program_code, 
            COUNT(s.student_id) as student_count
        FROM programs p
        LEFT JOIN students s ON p.program_id = s.program_id
        WHERE p.department_id IN ($placeholders)
        GROUP BY p.program_id
        ORDER BY student_count DESC
    ");
    $programStmt->execute($departmentIds);
    $programs = $programStmt->fetchAll(PDO::FETCH_ASSOC);

    // 8. Get sector statistics for the current period
    $sectorStats = [
        'college' => ['applied' => 0, 'completed' => 0],
        'shs' => ['applied' => 0, 'completed' => 0],
        'faculty' => ['applied' => 0, 'completed' => 0]
    ];

    if ($activePeriod) {
        // Get clearance stats for each sector
        $sectorStatsSql = "
            SELECT 
                cf.clearance_type,
                COUNT(DISTINCT cf.user_id) as applied_count,
                COUNT(DISTINCT CASE WHEN cf.clearance_form_progress = 'Completed' THEN cf.user_id END) as completed_count
            FROM clearance_forms cf
            LEFT JOIN students s ON cf.user_id = s.user_id
            LEFT JOIN faculty f ON cf.user_id = f.user_id
            WHERE (COALESCE(s.department_id, f.department_id) IN ($placeholders))
              AND cf.academic_year_id = ? 
              AND cf.semester_id = ? 
              AND cf.clearance_type IN ('College', 'Senior High School', 'Faculty')
            GROUP BY cf.clearance_type
        ";
        $sectorStatsStmt = $pdo->prepare($sectorStatsSql);
        $sectorStatsStmt->execute(array_merge($departmentIds, [$activePeriod['academic_year_id'], $activePeriod['semester_id']]));
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

    // 9. Assemble the final response
    $summaryData = [
        'user' => [
            'first_name' => $user['first_name'] ?? null,
            'last_name' => $user['last_name'] ?? null
        ],
        'departments' => $departments,
        'active_period' => $activePeriod,
        'total_students' => (int)$totalStudents,
        'total_faculty' => (int)$totalFaculty,
        'pending_signatures' => [
            'student' => (int)($pendingSignatures['student'] ?? 0),
            'faculty' => (int)($pendingSignatures['faculty'] ?? 0)
        ],
        'clearance_stats' => $clearanceStats,
        'sector_stats' => $sectorStats,
        'programs' => $programs
    ];

    send_json_response(true, $summaryData);

} catch (Exception $e) {
    // Log the error for debugging
    error_log("Program Head Dashboard API Error: " . $e->getMessage());
    send_json_response(false, [], 'An error occurred while fetching dashboard data.', 500);
}
?>
