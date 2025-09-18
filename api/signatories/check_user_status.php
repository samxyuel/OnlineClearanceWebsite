<?php
/**
 * Check User Signatory Status API
 * Returns whether the current user is assigned as a signatory for any clearance type
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../includes/config/database.php';
require_once '../../includes/classes/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $userId = $auth->getUserId();
    
    // Get user's signatory assignments
    $sql = "
        SELECT 
            ssa.clearance_type,
            ssa.designation_id,
            ssa.is_program_head,
            ssa.department_id,
            d.designation_name,
            dept.department_name as department_name,
            s.sector_name
        FROM sector_signatory_assignments ssa
        LEFT JOIN designations d ON ssa.designation_id = d.designation_id
        LEFT JOIN departments dept ON ssa.department_id = dept.department_id
        LEFT JOIN sectors s ON dept.sector_id = s.sector_id
        WHERE ssa.user_id = ? AND ssa.is_active = 1
        ORDER BY ssa.clearance_type, d.designation_name
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group assignments by clearance type
    $signatoryStatus = [
        'is_signatory' => count($assignments) > 0,
        'assignments' => [],
        'clearance_types' => []
    ];
    
    foreach ($assignments as $assignment) {
        $clearanceType = $assignment['clearance_type'];
        
        if (!isset($signatoryStatus['assignments'][$clearanceType])) {
            $signatoryStatus['assignments'][$clearanceType] = [];
            $signatoryStatus['clearance_types'][] = $clearanceType;
        }
        
        $signatoryStatus['assignments'][$clearanceType][] = [
            'designation_id' => $assignment['designation_id'],
            'designation_name' => $assignment['designation_name'],
            'is_program_head' => (bool)$assignment['is_program_head'],
            'department_id' => $assignment['department_id'],
            'department_name' => $assignment['department_name'],
            'sector_name' => $assignment['sector_name']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'user_id' => $userId,
        'signatory_status' => $signatoryStatus
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
