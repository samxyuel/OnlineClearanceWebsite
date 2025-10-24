<?php
/**
 * API: List Students for Signatory
 *
 * Fetches a list of students (both College and Senior High) for a logged-in staff signatory.
 * It determines the students based on the staff's assigned designation and the active clearance period.
 * Supports filtering, searching, and pagination.
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

    // 1. Get the staff member's designation ID
    $staffStmt = $pdo->prepare("SELECT designation_id FROM staff WHERE user_id = ? AND is_active = 1");
    $staffStmt->execute([$userId]);
    $designationId = $staffStmt->fetchColumn();

    if (!$designationId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access Denied: You are not an active staff member with a designation.']);
        exit;
    }

    // 2. Get the active clearance period for the relevant sector
    $type = $_GET['type'] ?? 'student'; // 'student' or 'faculty'
    $sector = ($type === 'faculty') ? 'Faculty' : 'Student'; // Simplified sector for query

    $periodQuery = "SELECT period_id FROM clearance_periods WHERE status = 'Ongoing'";
    if ($sector === 'Student') {
        $periodQuery .= " AND sector IN ('College', 'Senior High School')";
    } else {
        $periodQuery .= " AND sector = 'Faculty'";
    }
    $activePeriodsStmt = $pdo->query($periodQuery);
    $activePeriodId = $activePeriodsStmt->fetchColumn();

    if (!$activePeriodId) {
        // Return an empty list if no period is active, which is not an error.
        $responseKey = ($type === 'faculty') ? 'faculty' : 'students';
        echo json_encode(['success' => true, 'total' => 0, $responseKey => [], 'page' => 1, 'limit' => 10]);
        exit;
    }

    // 3. Get filter and pagination parameters
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 20;
    $offset = ($page - 1) * $limit;
    $search = $_GET['search'] ?? '';
    $clearanceStatus = $_GET['clearance_status'] ?? '';
    $accountStatus = $_GET['account_status'] ?? '';
    // Other filters like program, year level can be added here.

    // 4. Build the query based on type
    if ($type === 'faculty') {
        $select = "
            SELECT SQL_CALC_FOUND_ROWS
                f.employee_number as id,
                u.user_id,
                u.first_name,
                u.last_name,
                d.department_name as program,
                f.employment_status as year_level,
                '' as section,
                u.account_status as account_status,
                cs.action as clearance_status,
                cs.signatory_id,
                cf.clearance_form_id
        ";
        $from = "
            FROM faculty f
            JOIN users u ON f.user_id = u.user_id
            LEFT JOIN departments d ON f.department_id = d.department_id
            JOIN clearance_periods cp ON cp.sector = 'Faculty' AND cp.status = 'Ongoing'
            LEFT JOIN clearance_forms cf ON f.user_id = cf.user_id AND cf.academic_year_id = cp.academic_year_id AND cf.semester_id = cp.semester_id
            LEFT JOIN clearance_signatories cs ON cf.clearance_form_id = cs.clearance_form_id AND cs.designation_id = :designationId
        ";
        $searchFields = ['u.first_name', 'u.last_name', 'f.employee_number', 'd.department_name'];
    } else { // Default to student
        $select = "
            SELECT SQL_CALC_FOUND_ROWS
                s.student_id as id,
                u.user_id,
                u.first_name,
                u.last_name,
                p.program_code as program,
                s.year_level,
                s.section,
                u.account_status as account_status,
                cs.action as clearance_status,
                cs.signatory_id,
                cf.clearance_form_id
        ";
        $from = "
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            JOIN programs p ON s.program_id = p.program_id
            JOIN clearance_periods cp ON cp.sector IN ('College', 'Senior High School') AND cp.status = 'Ongoing'
            LEFT JOIN clearance_forms cf ON s.user_id = cf.user_id AND cf.academic_year_id = cp.academic_year_id AND cf.semester_id = cp.semester_id
            LEFT JOIN clearance_signatories cs ON cf.clearance_form_id = cs.clearance_form_id AND cs.designation_id = :designationId
        ";
        $searchFields = ['u.first_name', 'u.last_name', 's.student_id', 'p.program_code'];
    }

    // The main WHERE clause is now simplified as the JOINs handle the period and signatory context.
    // We must ensure that the user being listed is part of a sector that the current signatory is assigned to.
    $userSectorCondition = ($type === 'faculty')
        ? "d.sector_id IN (SELECT sec.sector_id FROM sectors sec JOIN departments d ON sec.sector_id = d.sector_id JOIN staff s ON d.department_id = s.department_id WHERE s.user_id = :actingUserId)"
        : "p.department_id IN (SELECT d.department_id FROM departments d JOIN staff s ON d.department_id = s.department_id WHERE s.user_id = :actingUserId)";

    $where = "WHERE 1=1"; // The JOINs already filter by designation, but we can add sector check for robustness if needed.
    $params = [':designationId' => $designationId];

    // Apply filters
    if (!empty($search)) {
        $searchClauses = [];
        foreach ($searchFields as $field) {
            $searchClauses[] = "$field LIKE :search";
        }
        $where .= " AND (" . implode(' OR ', $searchClauses) . ")";
        $params[':search'] = "%$search%";
    }

    if (!empty($clearanceStatus) && $clearanceStatus !== 'all') {
        // A specific status is requested.
        $where .= " AND COALESCE(cs.action, 'unapplied') = :clearanceStatus";
        $params[':clearanceStatus'] = $clearanceStatus;
    } else {
        // By default, show actionable items (not 'unapplied')
        $where .= " AND COALESCE(cs.action, 'unapplied') != 'unapplied'";
    }

    if (!empty($accountStatus)) {
        $where .= " AND u.status = :accountStatus";
        $params[':accountStatus'] = $accountStatus;
    }

    $orderBy = " ORDER BY u.last_name, u.first_name";
    $limitClause = " LIMIT :limit OFFSET :offset";

    // Prepare and execute the main query
    $stmt = $pdo->prepare($select . $from . $where . $orderBy . $limitClause);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    foreach ($params as $key => &$val) {
        $stmt->bindParam($key, $val);
    }
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total count for pagination
    $total = $pdo->query("SELECT FOUND_ROWS()")->fetchColumn();

    // 5. Format and return the response
    $responseKey = ($type === 'faculty') ? 'faculty' : 'students';
    $response = [
        'success' => true,
        'total' => (int)$total,
        'page' => $page,
        'limit' => $limit,
        $responseKey => array_map(function ($item) {
            return [
                'id' => $item['id'],
                'user_id' => $item['user_id'],
                'name' => trim($item['first_name'] . ' ' . $item['last_name']),
                'program' => $item['program'],
                'year_level' => $item['year_level'], // For faculty, this is employment_status
                'section' => $item['section'],
                'account_status' => $item['account_status'],
                'clearance_status' => $item['clearance_status'] ?? 'unapplied',
                'clearance_form_id' => $item['clearance_form_id'],
                'signatory_id' => $item['signatory_id']
            ];
        }, $results)
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