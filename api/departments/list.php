<?php
// -----------------------------------------------------------------------------
// Departments List Endpoint (Step 2)
// Method: GET – Return departments with optional sector filter and PH indicator
// -----------------------------------------------------------------------------
// Query params supported:
//   ?sector=College|Senior%20High%20School|Faculty
//   ?q=ICT
//   ?include_ph=1            // include current program head info
//   ?page=1&limit=50         // optional pagination
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

$sector     = isset($_GET['sector']) ? trim($_GET['sector']) : '';
$q          = isset($_GET['q']) ? trim($_GET['q']) : '';
$includePH  = isset($_GET['include_ph']) && in_array($_GET['include_ph'], ['1','true','yes'], true);
$page       = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit      = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 100;
$offset     = ($page - 1) * $limit;

try {
    $pdo = Database::getInstance()->getConnection();

    // Always include sector information for cross-sector detection
    $select = [
        'dep.department_id AS department_id',
        'dep.department_name AS department_name',
        'dep.department_code AS department_code',
        'sec.sector_id AS sector_id',
        'sec.sector_name AS sector_name',
        'dep.is_active AS is_active'
    ];

    $joins = ['JOIN sectors sec ON dep.sector_id = sec.sector_id'];
    
    if ($includePH) {
        // Current active Program Head per department (enforce one PH per department)
        $joins[] = "LEFT JOIN staff s ON s.department_id = dep.department_id AND s.staff_category = 'Program Head' AND s.is_active = 1";
        $joins[] = "LEFT JOIN users u ON u.user_id = s.user_id";
        $joins[] = "LEFT JOIN designations d ON d.designation_id = s.designation_id";
        $select[] = 's.user_id AS current_program_head_user_id';
        $select[] = "CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) AS current_program_head_name";
        $select[] = 'd.designation_name AS current_program_head_designation';
        $select[] = 's.employee_number AS current_program_head_employee_number';
    }

    $where = ['dep.is_active = 1'];
    $namedParams = [];
    $widx = 0;

    if ($q !== '') {
        $widx++;
        $ph = ':w' . $widx;
        $where[] = '(dep.department_name LIKE ' . $ph . ' OR dep.department_code LIKE ' . $ph . ')';
        $namedParams[$ph] = '%' . $q . '%';
    }

    if ($sector !== '') {
        // Filter by specific sector
        $widx++;
        $ph = ':w' . $widx;
        $where[] = 'sec.sector_name = ' . $ph;
        $namedParams[$ph] = $sector;
    }

    $whereSql = 'WHERE ' . implode(' AND ', $where);

    // First, get all departments (without pagination limit for cross-sector grouping)
    $selectSql = 'SELECT ' . implode(', ', $select) . ' FROM departments dep ' . implode(' ', $joins) . ' ' . $whereSql . ' ORDER BY dep.department_name ASC, sec.sector_name ASC';
    
    $stmt = $pdo->prepare($selectSql);
    foreach ($namedParams as $name => $value) {
        $stmt->bindValue($name, $value);
    }
    $stmt->execute();
    $allRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group departments by name/code to detect cross-sector departments
    $grouped = [];
    foreach ($allRows as $dept) {
        $key = $dept['department_code'] ?: $dept['department_name'];
        if (!isset($grouped[$key])) {
            $grouped[$key] = [
                'department_name' => $dept['department_name'],
                'department_code' => $dept['department_code'],
                'sectors' => [],
                'is_shared' => false
            ];
            
            // Include Program Head info if requested (use first occurrence)
            if ($includePH) {
                $grouped[$key]['current_program_head_user_id'] = $dept['current_program_head_user_id'] ?? null;
                $grouped[$key]['current_program_head_name'] = $dept['current_program_head_name'] ?? null;
                $grouped[$key]['current_program_head_designation'] = $dept['current_program_head_designation'] ?? null;
                $grouped[$key]['current_program_head_employee_number'] = $dept['current_program_head_employee_number'] ?? null;
            }
        }
        
        $grouped[$key]['sectors'][] = [
            'sector_id' => (int)$dept['sector_id'],
            'sector_name' => $dept['sector_name'],
            'department_id' => (int)$dept['department_id']
        ];
    }

    // Mark as shared if multiple sectors
    foreach ($grouped as &$group) {
        $group['is_shared'] = count($group['sectors']) > 1;
    }
    unset($group); // Break reference

    // Convert to indexed array and apply pagination
    $departments = array_values($grouped);
    $total = count($departments);
    $totalPages = ceil($total / $limit);
    $paginatedDepartments = array_slice($departments, $offset, $limit);

    echo json_encode([
        'success' => true,
        'departments' => $paginatedDepartments,
        'page' => $page,
        'limit' => $limit,
        'total' => $total,
        'total_pages' => $totalPages
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
