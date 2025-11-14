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

    // Re-enable authentication to identify the user and their role
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        exit;
    }
    $currentUserId = $auth->getUserId();
    $userRole = $auth->getRoleName(); // Assuming Auth class can provide the role name

    $programHeadDepts = [];
    if ($userRole === 'Program Head') {
        $stmt = $pdo->prepare("SELECT department_id FROM user_department_assignments WHERE user_id = ?");
        $stmt->execute([$currentUserId]);
        $programHeadDepts = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (empty($programHeadDepts)) {
            // A program head with no departments sees no one.
            echo json_encode(['success'=> true, 'page' => $page, 'limit' => $limit, 'total' => 0, 'staff' => []]);
            exit;
        }
    }

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

    // If the user is a Program Head, filter staff list to their departments
    if (!empty($programHeadDepts)) {
        $placeholders = implode(',', array_fill(0, count($programHeadDepts), '?'));
        $where[] = "s.user_id IN (SELECT DISTINCT uda.user_id FROM user_department_assignments uda WHERE uda.department_id IN ($placeholders))";
        $params = array_merge($params, $programHeadDepts);
    }

    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    // Corrected table name to user_department_assignments
    $sql = "SELECT s.user_id, s.employee_number, u.first_name, u.last_name, u.username, u.email, u.contact_number,
                   s.department_id as staff_department_id, 
                   reg_dept.department_name as staff_department_name,
                   d.designation_id, d.designation_name, s.staff_category, s.employment_status, s.is_active,
                   GROUP_CONCAT(DISTINCT uda_dept.department_name ORDER BY uda_dept.department_name SEPARATOR '|') as assigned_departments,
                   GROUP_CONCAT(DISTINCT uda_dept.department_id ORDER BY uda_dept.department_id SEPARATOR '|') as assigned_department_ids,
                   GROUP_CONCAT(DISTINCT uda.is_primary ORDER BY uda_dept.department_name SEPARATOR '|') as is_primary_flags
            FROM staff s
            JOIN users u ON u.user_id = s.user_id
            LEFT JOIN designations d ON d.designation_id = s.designation_id
            LEFT JOIN user_department_assignments uda ON s.user_id = uda.user_id AND uda.is_active = 1
            LEFT JOIN departments uda_dept ON uda.department_id = uda_dept.department_id
            LEFT JOIN departments reg_dept ON s.department_id = reg_dept.department_id
            $whereSql
            GROUP BY s.user_id
            ORDER BY u.last_name ASC, u.first_name ASC
            LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    $paramIndex = 1;
    foreach ($params as $value) {
        $stmt->bindValue($paramIndex++, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count total (mirror joins and where)
    $countSql = "SELECT COUNT(DISTINCT s.employee_number)
                 FROM staff s
                 JOIN users u ON u.user_id = s.user_id
                 LEFT JOIN designations d ON d.designation_id = s.designation_id
                 $whereSql";
    $cntStmt = $pdo->prepare($countSql);
    $paramIndex = 1;
    foreach ($params as $value) {
        $cntStmt->bindValue($paramIndex++, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $cntStmt->execute();
    $cnt = $cntStmt->fetchColumn();

    // Process the rows to structure the data properly
    $processedRows = [];
    foreach ($rows as $row) {
        $processedRow = [
            'user_id' => $row['user_id'],
            'employee_number' => $row['employee_number'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'username' => $row['username'],
            'email' => $row['email'],
            'contact_number' => $row['contact_number'],
            'designation_id' => $row['designation_id'],
            'designation_name' => $row['designation_name'],
            'staff_category' => $row['staff_category'],
            'employment_status' => $row['employment_status'],
            'is_active' => $row['is_active'],
            'department_id' => $row['staff_department_id'], 
            'department_name' => $row['staff_department_name'],
            'departments' => [],
        ];
        
        if (!empty($row['assigned_departments'])) {
            $deptNames = explode('|', $row['assigned_departments']);
            $deptIds = explode('|', $row['assigned_department_ids']);
            $isPrimaryFlags = explode('|', $row['is_primary_flags']);
            
            for ($i = 0; $i < count($deptNames); $i++) {
                if (!empty($deptNames[$i])) {
                    $processedRow['departments'][] = [
                        'id' => $deptIds[$i] ?? null,
                        'name' => $deptNames[$i],
                        'is_primary' => ($isPrimaryFlags[$i] ?? '0') === '1'
                    ];
                }
            }
        }
        
        $processedRows[] = $processedRow;
    }

    echo json_encode([
        'success'=> true,
        'page'   => $page,
        'limit'  => $limit,
        'total'  => (int)$cnt,
        'staff'  => $processedRows,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error: '.$e->getMessage()]);
}
