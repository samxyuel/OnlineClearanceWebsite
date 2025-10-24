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

    $sql = "SELECT s.user_id, s.employee_number, u.first_name, u.last_name, u.username, u.email, u.contact_number,
                   s.department_id as staff_department_id, 
                   reg_dept.department_name as staff_department_name, /* Direct department name for non-PH */
                   d.designation_id, d.designation_name, s.staff_category, s.employment_status, s.is_active,
                   GROUP_CONCAT(DISTINCT sda_dept.department_name ORDER BY sda_dept.department_name SEPARATOR '|') as assigned_departments,
                   GROUP_CONCAT(DISTINCT sda_dept.department_id ORDER BY sda_dept.department_id SEPARATOR '|') as assigned_department_ids,
                   GROUP_CONCAT(DISTINCT sda_sec.sector_name ORDER BY sda_sec.sector_name SEPARATOR '|') as assigned_sectors,
                   GROUP_CONCAT(DISTINCT sda_sec.sector_id ORDER BY sda_sec.sector_id SEPARATOR '|') as assigned_sector_ids,
                   GROUP_CONCAT(DISTINCT sda.is_primary ORDER BY sda_dept.department_name SEPARATOR '|') as is_primary_flags
            FROM staff s
            JOIN users u ON u.user_id = s.user_id
            LEFT JOIN designations d ON d.designation_id = s.designation_id
            LEFT JOIN staff_department_assignments sda ON s.user_id = sda.staff_id AND sda.is_active = 1
            LEFT JOIN departments sda_dept ON sda.department_id = sda_dept.department_id
            LEFT JOIN sectors sda_sec ON sda.sector_id = sda_sec.sector_id
            LEFT JOIN departments reg_dept ON s.department_id = reg_dept.department_id
            $whereSql
            GROUP BY s.user_id
            ORDER BY u.last_name ASC, u.first_name ASC
            LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k=>$v) { $stmt->bindValue($k, $v, PDO::PARAM_STR); }
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count total (mirror joins and where)
    $countSql = "SELECT COUNT(DISTINCT s.employee_number)
                 FROM staff s
                 JOIN users u ON u.user_id = s.user_id
                 LEFT JOIN designations d ON d.designation_id = s.designation_id
                 LEFT JOIN staff_department_assignments sda ON s.user_id = sda.staff_id AND sda.is_active = 1
                 LEFT JOIN departments sda_dept ON sda.department_id = sda_dept.department_id
                 LEFT JOIN sectors sda_sec ON sda.sector_id = sda_sec.sector_id
                 $whereSql";
    $cntStmt = $pdo->prepare($countSql);
    foreach ($params as $k=>$v) { $cntStmt->bindValue($k, $v, PDO::PARAM_STR); }
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
            'department_name' => $row['staff_department_name'], // For non-PH staff
            'departments' => [],
            'sectors' => []
        ];
        
        // Parse departments
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
        
        // Parse sectors
        if (!empty($row['assigned_sectors'])) {
            $sectorNames = explode('|', $row['assigned_sectors']);
            $sectorIds = explode('|', $row['assigned_sector_ids']);
            
            for ($i = 0; $i < count($sectorNames); $i++) {
                if (!empty($sectorNames[$i])) {
                    $processedRow['sectors'][] = [
                        'id' => $sectorIds[$i] ?? null,
                        'name' => $sectorNames[$i]
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
