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

    // Detect sector column dynamically (sector or sector_id) to avoid schema mismatch
    $colStmt = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'departments' AND COLUMN_NAME IN ('sector', 'sector_id')");
    $colStmt->execute();
    $cols = $colStmt->fetchAll(PDO::FETCH_COLUMN, 0);
    $hasSectorName = in_array('sector', $cols, true);
    $hasSectorId   = in_array('sector_id', $cols, true);

    $select = [
        'dep.department_id AS department_id',
        'dep.department_name AS department_name'
    ];
    if ($hasSectorName) {
        $select[] = 'dep.sector AS sector';
    }
    if ($hasSectorId) {
        $select[] = 'dep.sector_id AS sector_id';
    }

    $joins = [];
    if ($includePH) {
        // Current active Program Head per department (enforce one PH per department)
        $joins[] = "LEFT JOIN staff s ON s.department_id = dep.department_id AND s.staff_category = 'Program Head' AND s.is_active = 1";
        $joins[] = "LEFT JOIN users u ON u.user_id = s.user_id";
        $joins[] = "LEFT JOIN designations d ON d.designation_id = s.designation_id";
        $select[] = 's.user_id AS current_program_head_user_id';
        $select[] = "CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) AS current_program_head_name";
        $select[] = 'd.designation_name AS current_program_head_designation';
    }

    $where = [];
    $namedParams = [];
    $widx = 0;

    if ($q !== '') {
        $widx++;
        $ph = ':w' . $widx;
        $where[] = 'dep.department_name LIKE ' . $ph;
        $namedParams[$ph] = '%' . $q . '%';
    }

    if ($sector !== '') {
        $appliedSectorFilter = false;
        if ($hasSectorName) {
            $widx++;
            $ph = ':w' . $widx;
            $where[] = 'dep.sector = ' . $ph;
            $namedParams[$ph] = $sector;
            $appliedSectorFilter = true;
        } elseif ($hasSectorId) {
            // If sector_id exists but we received a name, try to resolve via sectors table
            try {
                $sidStmt = $pdo->prepare("SELECT sector_id FROM sectors WHERE sector_name = ? LIMIT 1");
                $sidStmt->execute([$sector]);
                $secId = $sidStmt->fetchColumn();
                if ($secId) {
                    $widx++;
                    $ph = ':w' . $widx;
                    $where[] = 'dep.sector_id = ' . $ph;
                    $namedParams[$ph] = (int)$secId;
                    $appliedSectorFilter = true;
                }
            } catch (Exception $e) {
                // no sectors table or lookup failed
            }
        }
        // strict mode: if sector filter was requested but could not be applied, return empty
        if (!$appliedSectorFilter) {
            echo json_encode([
                'success' => true,
                'departments' => [],
                'page' => $page,
                'limit' => $limit,
                'total' => 0,
                'total_pages' => 0
            ]);
            exit;
        }
    }

    $whereSql = '';
    if (!empty($where)) {
        $whereSql = 'WHERE ' . implode(' AND ', $where);
    }

    $selectSql = 'SELECT ' . implode(', ', $select) . ' FROM departments dep ' . implode(' ', $joins) . ' ' . $whereSql . ' ORDER BY dep.department_name ASC LIMIT :limit OFFSET :offset';

    // Total count (without pagination)
    $countSql  = 'SELECT COUNT(*) FROM departments dep ' . $whereSql;

    $countStmt = $pdo->prepare($countSql);
    foreach ($namedParams as $name => $value) {
        $countStmt->bindValue($name, $value);
    }
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();

    $stmt = $pdo->prepare($selectSql);
    foreach ($namedParams as $name => $value) {
        $stmt->bindValue($name, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'departments' => $rows,
        'page' => $page,
        'limit' => $limit,
        'total' => $total,
        'total_pages' => ceil($total / $limit)
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
// -----------------------------------------------------------------------------
// End of file
// -----------------------------------------------------------------------------
