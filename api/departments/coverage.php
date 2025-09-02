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

try {
    $pdo = Database::getInstance()->getConnection();

    // Total departments per sector
    $sqlTotals = "SELECT s.sector_name, COUNT(d.department_id) AS total
                  FROM sectors s
                  LEFT JOIN departments d ON d.sector_id = s.sector_id AND d.is_active = 1
                  GROUP BY s.sector_id";
    $totals = $pdo->query($sqlTotals)->fetchAll(PDO::FETCH_KEY_PAIR);

    // Assigned (has active Program Head) per sector
    $sqlAssigned = "SELECT s.sector_name, COUNT(DISTINCT d.department_id) AS assigned
                    FROM sectors s
                    LEFT JOIN departments d ON d.sector_id = s.sector_id AND d.is_active = 1
                    LEFT JOIN staff st ON st.department_id = d.department_id AND st.staff_category = 'Program Head' AND st.is_active = 1
                    GROUP BY s.sector_id";
    $assigned = $pdo->query($sqlAssigned)->fetchAll(PDO::FETCH_KEY_PAIR);

    $data = [];
    foreach ($totals as $sectorName => $totalCount) {
        $a = (int)($assigned[$sectorName] ?? 0);
        $t = (int)$totalCount;
        $u = max(0, $t - $a);
        $data[$sectorName] = [
            'total' => $t,
            'assigned' => $a,
            'unassigned' => $u
        ];
    }

    echo json_encode(['success' => true, 'coverage' => $data]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}


