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

$sector = isset($_GET['sector']) ? trim($_GET['sector']) : '';
if ($sector === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'sector is required']);
    exit;
}

try {
    $pdo = Database::getInstance()->getConnection();

    // Resolve sector_id
    $sidStmt = $pdo->prepare("SELECT sector_id FROM sectors WHERE sector_name = ? LIMIT 1");
    $sidStmt->execute([$sector]);
    $sectorId = $sidStmt->fetchColumn();
    if (!$sectorId) {
        echo json_encode(['success' => true, 'departments' => []]);
        exit;
    }

    $sql = "SELECT d.department_id, d.department_name
            FROM departments d
            LEFT JOIN staff st ON st.department_id = d.department_id AND st.staff_category = 'Program Head' AND st.is_active = 1
            WHERE d.sector_id = :sid AND d.is_active = 1 AND st.employee_number IS NULL
            ORDER BY d.department_name";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':sid', (int)$sectorId, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'departments' => $rows]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}


