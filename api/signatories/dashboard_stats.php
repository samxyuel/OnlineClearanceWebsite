<?php
// -----------------------------------------------------------------------------
// Signatory Dashboard Summary Endpoint (Phase 3B – step 5)
// Method: GET – Provides counts / aggregates for dashboards
// -----------------------------------------------------------------------------
// Example response:
// {
//   "success": true,
//   "data": {
//     "total_signatories": 42,
//     "program_heads": 8,
//     "school_administrators": 3,
//     "regular_staff": 31
//   }
// }
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

try {
    $db         = Database::getInstance();
    $connection = $db->getConnection();

    $countsSql = "SELECT 
                    COUNT(*)                                        AS total,
                    SUM(staff_category='Program Head')             AS program_heads,
                    SUM(staff_category='School Administrator')     AS school_administrators,
                    SUM(staff_category='Regular Staff')            AS regular_staff
                  FROM staff
                  WHERE is_active = 1";
    $row = $connection->query($countsSql)->fetch(PDO::FETCH_ASSOC);

    $counts = [
        'total_signatories'      => (int)$row['total'],
        'program_heads'         => (int)$row['program_heads'],
        'school_administrators' => (int)$row['school_administrators'],
        'regular_staff'         => (int)$row['regular_staff']
    ];

    echo json_encode(['success' => true, 'data' => $counts]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
