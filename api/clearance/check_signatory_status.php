<?php
/**
 * Check Signatory Status API
 * Returns whether the current user is assigned as a signatory for a specific sector
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../includes/config/database.php';
require_once '../../includes/classes/Auth.php';

try {
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit();
    }

    $userId = (int)$auth->getUserId();
    $sector = $_GET['sector'] ?? '';
    
    if (empty($sector)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Sector parameter required']);
        exit();
    }

    $pdo = Database::getInstance()->getConnection();
    
    // A user is a signatory for a sector if they have an active assignment
    // for that clearance_type. This check is primarily for Program Heads.
    $sql = "SELECT COUNT(*) as count 
            FROM sector_signatory_assignments ssa
            JOIN designations d ON ssa.designation_id = d.designation_id
            WHERE ssa.user_id = ? 
              AND ssa.is_active = 1 
              AND d.designation_name = 'Program Head' 
              AND ssa.clearance_type = ?";
    $params = [$userId, $sector];
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $isSignatory = (int)$result['count'] > 0;
    
    echo json_encode([
        'success' => true,
        'is_signatory' => $isSignatory,
        'sector' => $sector,
        'user_id' => $userId
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error checking signatory status: ' . $e->getMessage()
    ]);
}
?>
