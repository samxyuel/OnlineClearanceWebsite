<?php
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
// Temporarily disable auth for testing
// if (!$auth->isLoggedIn()) {
//     http_response_code(401);
//     echo json_encode(['success' => false, 'message' => 'Authentication required']);
//     exit;
// }

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$page  = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? max(1, min(200, (int)$_GET['limit'])) : 100;
$offset = ($page - 1) * $limit;
$q     = isset($_GET['search']) ? trim($_GET['search']) : '';
$excludePH = isset($_GET['exclude_program_head']) && ($_GET['exclude_program_head'] === '1' || $_GET['exclude_program_head'] === 'true');

try {
    $db  = Database::getInstance();
    $pdo = $db->getConnection();

    // Build where clauses
    $where = ['s.is_active = 1', "s.employee_number REGEXP '^LCA[0-9]{4}[A-Z]$'"];
    $params = [];
    if ($excludePH) {
        $where[] = "(d.designation_name IS NULL OR d.designation_name <> 'Program Head')";
    }
    if ($q !== '') {
        $where[] = "(u.first_name LIKE :q1 OR u.last_name LIKE :q2 OR u.username LIKE :q3 OR s.employee_number LIKE :q4)";
        $params[':q1'] = "%$q%";
        $params[':q2'] = "%$q%";
        $params[':q3'] = "%$q%";
        $params[':q4'] = "%$q%";
    }
    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $sql = "SELECT s.user_id, s.employee_number, u.first_name, u.last_name, u.username, d.designation_name
            FROM staff s
            JOIN users u ON u.user_id = s.user_id
            LEFT JOIN designations d ON d.designation_id = s.designation_id
            $whereSql
            ORDER BY u.last_name ASC, u.first_name ASC
            LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k=>$v) { $stmt->bindValue($k, $v, PDO::PARAM_STR); }
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count total (mirror joins and where)
    $countSql = "SELECT COUNT(*)
                 FROM staff s
                 JOIN users u ON u.user_id = s.user_id
                 LEFT JOIN designations d ON d.designation_id = s.designation_id
                 $whereSql";
    $cntStmt = $pdo->prepare($countSql);
    foreach ($params as $k=>$v) { $cntStmt->bindValue($k, $v, PDO::PARAM_STR); }
    $cntStmt->execute();
    $cnt = $cntStmt->fetchColumn();

    echo json_encode([
        'success'=> true,
        'page'   => $page,
        'limit'  => $limit,
        'total'  => (int)$cnt,
        'staff'  => $rows,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error: '.$e->getMessage()]);
}


