<?php
// -----------------------------------------------------------------------------
// List Signatories Endpoint (Phase 3B – step 3)
// Method: GET – Return signatories with optional filters, pagination
// -----------------------------------------------------------------------------
// Query params supported:
//   ?page=1&limit=20&department_id=5&designation=Program%20Head
// -----------------------------------------------------------------------------

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once '../../includes/config/database.php';
require_once '../../includes/classes/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$page  = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 20;
$offset = ($page - 1) * $limit;

$filters = [];
$params  = [];

// Switch to signatory_assignments as source of truth
$filters[] = 'sa.is_active = 1';

if (!empty($_GET['department_id'])) {
    $filters[] = 'sa.department_id = :department_id';
    $params[':department_id']  = (int)$_GET['department_id'];
}
if (!empty($_GET['designation'])) {
    $filters[] = 'd.designation_name = :designation';
    $params[':designation']  = $_GET['designation'];
}
if (!empty($_GET['clearance_type'])) {
    $filters[] = 'sa.clearance_type = :clearance_type';
    $params[':clearance_type']  = $_GET['clearance_type'];
}

$whereSql = 'WHERE ' . implode(' AND ', $filters);

try {
    $pdo = Database::getInstance()->getConnection();

    // 1) Total count
    $countSql  = "SELECT COUNT(*) FROM signatory_assignments sa
                  JOIN designations d ON d.designation_id = sa.designation_id
                  $whereSql";
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $name=>$value) {
        $countStmt->bindValue($name, $value);
    }
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();

    // 2) Fetch rows with joins
    $selectSql = "SELECT
                      st.employee_number,
                      sa.user_id,
                      u.username,
                      u.first_name,
                      u.last_name,
                      d.designation_id,
                      d.designation_name,
                      sa.clearance_type,
                      sa.department_id,
                      dep.department_name,
                      sa.is_active,
                      sa.created_at,
                      sa.updated_at
                  FROM signatory_assignments sa
                  JOIN users u         ON u.user_id = sa.user_id
                  JOIN designations d  ON d.designation_id = sa.designation_id
                  LEFT JOIN departments dep ON dep.department_id = sa.department_id
                  LEFT JOIN staff st   ON st.user_id = sa.user_id
                  $whereSql
                  ORDER BY d.designation_name, u.last_name
                  LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($selectSql);
    foreach ($params as $name => $value) {
        $stmt->bindValue($name, $value);
    }
    $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success'     => true,
        'signatories' => $rows,
        'page'        => $page,
        'limit'       => $limit,
        'total'       => $total,
        'total_pages' => ceil($total / $limit)
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
// -----------------------------------------------------------------------------
// End of file
// -----------------------------------------------------------------------------
