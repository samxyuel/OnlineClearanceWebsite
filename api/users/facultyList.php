<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');

require_once '../../includes/config/database.php';
require_once '../../includes/classes/Auth.php';

try {
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied.']);
        exit;
    }

    $pdo = Database::getInstance()->getConnection();

    // Parameters
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 20;
    $offset = ($page - 1) * $limit;
    $search = trim($_GET['search'] ?? '');
    $clearanceStatus = $_GET['clearance_status'] ?? '';
    $accountStatus = $_GET['account_status'] ?? '';
    $employmentStatus = $_GET['employment_status'] ?? '';
    $departmentId = $_GET['departments'] ?? '';

    $baseQuery = "
        FROM faculty f
        JOIN users u ON f.user_id = u.user_id
        LEFT JOIN departments d ON f.department_id = d.department_id
        LEFT JOIN clearance_forms cf ON u.user_id = cf.user_id AND cf.academic_year_id = (SELECT academic_year_id FROM clearance_periods WHERE status = 'Ongoing' AND sector = 'Faculty' LIMIT 1) AND cf.semester_id = (SELECT semester_id FROM clearance_periods WHERE status = 'Ongoing' AND sector = 'Faculty' LIMIT 1)
    ";
    $selectFields = "
        f.employee_number, u.user_id, u.first_name, u.last_name, u.middle_name,
        u.email, u.contact_number, d.department_name, f.employment_status, u.account_status,
        COALESCE(cf.clearance_form_progress, 'Unapplied') as clearance_status
    ";
    $searchFields = ['u.first_name', 'u.last_name', 'f.employee_number', 'd.department_name'];

    $where = "WHERE 1=1";
    $params = [];

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
    if ($employmentStatus) {
        $where .= " AND f.employment_status = :employmentStatus";
        $params[':employmentStatus'] = $employmentStatus;
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
        $where .= " AND f.department_id = :departmentId";
        $params[':departmentId'] = $departmentId;
    }

    // Get total count
    $countStmt = $pdo->prepare("SELECT COUNT(u.user_id) $baseQuery $where");
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();

    // Get paginated data
    $dataStmt = $pdo->prepare("SELECT $selectFields $baseQuery $where ORDER BY u.last_name, u.first_name LIMIT :limit OFFSET :offset");
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

    // Bind the same parameters as the main query for accurate stats
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
        'resigned' => $statsResults['resigned'] ?? 0,
    ];

    echo json_encode([
        'success' => true,
        'total' => (int)$total,
        'page' => $page,
        'limit' => $limit,
        'stats' => $stats,
        'faculty' => $results
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}
?>
