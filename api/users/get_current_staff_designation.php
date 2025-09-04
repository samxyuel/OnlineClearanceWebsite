<?php
// Get current staff user's designation
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(204); 
    exit; 
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') { 
    http_response_code(405); 
    echo json_encode(['success' => false, 'message' => 'Method not allowed']); 
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

try {
    $pdo = Database::getInstance()->getConnection();
    $userId = $auth->getUserId();
    
    // Get staff designation for the current user
    $stmt = $pdo->prepare("
        SELECT d.designation_name, s.staff_category
        FROM staff s
        JOIN designations d ON d.designation_id = s.designation_id
        WHERE s.user_id = ? AND s.is_active = 1
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'designation_name' => $result['designation_name'],
            'staff_category' => $result['staff_category']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No active staff designation found for user'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
