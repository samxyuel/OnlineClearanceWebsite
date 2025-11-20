<?php
// API: Staff Designation Assignments
// GET: /api/staff/designation_assignments.php?user_id=123 OR ?staff_id=123

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../includes/config/database.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

function json_resp($code, $payload) {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
} catch (Exception $e) {
    json_resp(500, ['success' => false, 'message' => 'Database connection failed']);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method !== 'GET') {
    json_resp(405, ['success' => false, 'message' => 'Method not allowed']);
}

$userId = isset($_GET['user_id']) ? trim($_GET['user_id']) : (isset($_GET['staff_id']) ? trim($_GET['staff_id']) : null);
if (!$userId) {
    json_resp(400, ['success' => false, 'message' => 'user_id (or staff_id) is required']);
}

try {
    $stmt = $db->prepare("SELECT uda.user_id, uda.designation_id, uda.is_primary, d.designation_name
                         FROM user_designation_assignments uda
                         JOIN designations d ON uda.designation_id = d.designation_id
                         WHERE uda.user_id = :user_id AND uda.is_active = 1
                         ORDER BY uda.is_primary DESC, d.designation_name ASC");
    $stmt->execute([':user_id' => $userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Normalize numeric ids
    $designations = array_map(function($r){
        return [
            'designation_id' => (int)$r['designation_id'],
            'designation_name' => $r['designation_name'],
            'is_primary' => isset($r['is_primary']) ? (bool)$r['is_primary'] : false
        ];
    }, $rows);

    json_resp(200, ['success' => true, 'designations' => $designations]);
} catch (PDOException $e) {
    json_resp(500, ['success' => false, 'message' => 'Database error']);
}

?>
