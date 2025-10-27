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
    
    // Special handling for Senior High School Program Head
    if ($sector === 'Senior High School') {
        // For SHS, a Program Head is valid if they are assigned to ANY department
        // with a department_type of 'Senior High School'.
        $sql = "SELECT COUNT(*) as count 
                FROM signatory_assignments sa
                JOIN designations d ON sa.designation_id = d.designation_id
                JOIN departments dept ON sa.department_id = dept.department_id
                WHERE sa.user_id = ? 
                AND sa.is_active = 1 
                AND d.designation_name = 'Program Head'
                AND dept.department_type = 'Senior High School'";
        $params = [$userId];
    } else {
        // For other sectors (e.g., College), check assignment within the sector.
        $sql = "SELECT COUNT(*) as count 
                FROM signatory_assignments sa
                JOIN designations d ON sa.designation_id = d.designation_id
                JOIN departments dept ON sa.department_id = dept.department_id
                JOIN sectors s ON dept.sector_id = s.sector_id
                WHERE sa.user_id = ? AND sa.is_active = 1 AND d.designation_name = 'Program Head' AND s.sector_name = ?";
        $params = [$userId, $sector];
    }
    
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
