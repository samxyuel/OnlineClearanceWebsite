<?php
/**
 * API: List Users (Students/Faculty)
 *
 * A flexible endpoint for fetching lists of users with filtering, pagination, and statistics.
 * Designed for administrative views.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/config/database.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

try {
    $auth = new Auth();
    if (!$auth->isLoggedIn() || !$auth->hasPermission('view_users')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied.']);
        exit;
    }

    $pdo = Database::getInstance()->getConnection();

    // Parameters
    $type = $_GET['type'] ?? 'student'; // 'student' or 'faculty'
    $sector = $_GET['sector'] ?? null; // 'College', 'Senior High School'
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 20;
    $offset = ($page - 1) * $limit;
    $search = trim($_GET['search'] ?? '');
    $clearanceStatus = $_GET['clearance_status'] ?? '';
    $programId = $_GET['program_id'] ?? '';
    $yearLevel = $_GET['year_level'] ?? '';
    $accountStatus = $_GET['account_status'] ?? '';
    $departmentId = $_GET['departments'] ?? '';

    $baseQuery = "";
    $countQuery = "";
    $params = [];
    $selectFields = "";
    $searchFields = [];

    if ($type === 'student') {
        $baseQuery = "
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            LEFT JOIN programs p ON s.program_id = p.program_id
            LEFT JOIN departments d ON s.department_id = d.department_id
            LEFT JOIN clearance_forms cf ON u.user_id = cf.user_id AND cf.academic_year_id = (SELECT academic_year_id FROM clearance_periods WHERE status = 'Ongoing' AND sector = s.sector LIMIT 1) AND cf.semester_id = (SELECT semester_id FROM clearance_periods WHERE status = 'Ongoing' AND sector = s.sector LIMIT 1)
        ";
        $selectFields = "
            s.student_id as id, u.user_id, CONCAT(u.first_name, ' ', u.last_name) as name,
            p.program_name as program, s.year_level, s.section, u.account_status,
            COALESCE(cf.clearance_form_progress, 'Unapplied') as clearance_status
        ";
        $searchFields = ['u.first_name', 'u.last_name', 's.student_id', 'p.program_name'];
    } else { // faculty
        $baseQuery = "
            FROM faculty f
            JOIN users u ON f.user_id = u.user_id
            LEFT JOIN departments d ON f.department_id = d.department_id
            LEFT JOIN clearance_forms cf ON u.user_id = cf.user_id AND cf.academic_year_id = (SELECT academic_year_id FROM clearance_periods WHERE status = 'Ongoing' AND sector = 'Faculty' LIMIT 1) AND cf.semester_id = (SELECT semester_id FROM clearance_periods WHERE status = 'Ongoing' AND sector = 'Faculty' LIMIT 1)
        ";
        $selectFields = "
            f.employee_number as id, u.user_id, CONCAT(u.first_name, ' ', u.last_name) as name,
            d.department_name as program, f.employment_status as year_level, '' as section, u.account_status,
            COALESCE(cf.clearance_form_progress, 'Unapplied') as clearance_status
        ";
        $searchFields = ['u.first_name', 'u.last_name', 'f.employee_number', 'd.department_name'];
    }

    $where = "WHERE 1=1";
    if ($sector) {
        $where .= " AND s.sector = :sector"; // This assumes faculty also have a sector column if you filter them by it.
        $params[':sector'] = $sector;
    }
    if ($search) {
        $searchClauses = [];
        $searchParamIndex = 0;

        // Add a clause for full name search first
        $searchClauses[] = "CONCAT(u.first_name, ' ', u.last_name) LIKE :search_fullname";
        $params[':search_fullname'] = "%$search%";

        foreach ($searchFields as $field) {
            // Avoid duplicating name search which is already handled by CONCAT
            if ($field !== 'u.first_name' && $field !== 'u.last_name') {
                $paramName = ":search" . $searchParamIndex++;
                $searchClauses[] = "$field LIKE $paramName";
                $params[$paramName] = "%$search%";
            }
        }
        $where .= " AND (" . implode(' OR ', $searchClauses) . ")";
    }
    if ($programId) {
        $where .= " AND s.program_id = :programId";
        $params[':programId'] = $programId;
    }
    if ($yearLevel) {
        $where .= " AND s.year_level = :yearLevel";
        $params[':yearLevel'] = $yearLevel;
    }
    if ($accountStatus) {
        $where .= " AND u.account_status = :accountStatus";
        $params[':accountStatus'] = $accountStatus;
    }
    if ($clearanceStatus) {
        $where .= " AND COALESCE(cf.clearance_form_progress, 'Unapplied') = :clearanceStatus";
        $params[':clearanceStatus'] = $clearanceStatus;
    }
    if ($departmentId) {
        $where .= ($type === 'student' ? " AND p.department_id = :departmentId" : " AND f.department_id = :departmentId");
        $params[':departmentId'] = $departmentId;
    }

    // Get total count
    $countStmt = $pdo->prepare("SELECT COUNT(u.user_id) $baseQuery $where");
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();

    // Get paginated data
    $dataStmt = $pdo->prepare("SELECT SQL_CALC_FOUND_ROWS $selectFields $baseQuery $where ORDER BY u.last_name, u.first_name LIMIT :limit OFFSET :offset");
    $dataStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    foreach ($params as $key => &$val) {
        $dataStmt->bindParam($key, $val);
    }
    $dataStmt->execute();
    $results = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get statistics
    $statsQuery = "SELECT u.account_status, COUNT(*) as count $baseQuery $where GROUP BY u.account_status";
    $statsStmt = $pdo->prepare($statsQuery);
    
    // We need to bind the same parameters as the main query for the stats to be accurate
    $statsParams = $params;
    foreach ($statsParams as $key => &$val) {
        $statsStmt->bindParam($key, $val);
    }
    $statsStmt->execute();

    $statsResults = $statsStmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $stats = [
        'total' => array_sum($statsResults),
        'active' => $statsResults['active'] ?? 0,
        'inactive' => $statsResults['inactive'] ?? 0,
        'graduated' => $statsResults['graduated'] ?? 0,
    ];

    echo json_encode([
        'success' => true,
        'total' => (int)$total,
        'page' => $page,
        'limit' => $limit,
        'stats' => $stats,
        'students' => $results
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}
?>
