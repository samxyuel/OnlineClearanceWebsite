<?php
// -----------------------------------------------------------------------------
// Program Head Assignments API
// Method: GET - Get Program Head assignments by sector or department
// -----------------------------------------------------------------------------
// Query Parameters:
//   - sector_id: Get Program Heads for a specific sector
//   - department_id: Get Program Head for a specific department
//   - clearance_type: Filter by clearance type (student/faculty)
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
    $pdo = Database::getInstance()->getConnection();
    
    $sectorId = isset($_GET['sector_id']) ? (int)$_GET['sector_id'] : null;
    $departmentId = isset($_GET['department_id']) ? (int)$_GET['department_id'] : null;
    $clearanceType = isset($_GET['clearance_type']) ? strtolower(trim($_GET['clearance_type'])) : null;
    
    // Validate clearance type if provided
    if ($clearanceType && !in_array($clearanceType, ['student', 'faculty'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid clearance_type. Must be student or faculty']);
        exit;
    }
    
    // Get Program Head designation ID
    $phStmt = $pdo->prepare("SELECT designation_id FROM designations WHERE designation_name = 'Program Head' AND is_active = 1 LIMIT 1");
    $phStmt->execute();
    $programHeadDesignationId = $phStmt->fetchColumn();
    
    if (!$programHeadDesignationId) {
        echo json_encode(['success' => true, 'program_heads' => []]);
        exit;
    }
    
    if ($departmentId) {
        // Get Program Head for specific department
        $sql = "SELECT 
                    sa.assignment_id,
                    sa.user_id,
                    sa.department_id,
                    sa.sector_id,
                    d.department_name,
                    s.sector_name,
                    CONCAT(u.first_name, ' ', u.last_name) AS full_name,
                    u.first_name,
                    u.last_name,
                    st.employee_number
                FROM signatory_assignments sa
                JOIN departments d ON d.department_id = sa.department_id
                JOIN sectors s ON s.sector_id = sa.sector_id
                JOIN users u ON u.user_id = sa.user_id
                LEFT JOIN staff st ON st.user_id = sa.user_id AND st.is_active = 1
                WHERE sa.designation_id = ? 
                AND sa.department_id = ? 
                AND sa.is_active = 1
                LIMIT 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$programHeadDesignationId, $departmentId]);
        $programHead = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'program_head' => $programHead ?: null,
            'department_id' => $departmentId
        ]);
        
    } elseif ($sectorId) {
        // Get all Program Heads for specific sector
        $sql = "SELECT 
                    sa.assignment_id,
                    sa.user_id,
                    sa.department_id,
                    sa.sector_id,
                    d.department_name,
                    s.sector_name,
                    CONCAT(u.first_name, ' ', u.last_name) AS full_name,
                    u.first_name,
                    u.last_name,
                    st.employee_number
                FROM signatory_assignments sa
                JOIN departments d ON d.department_id = sa.department_id
                JOIN sectors s ON s.sector_id = sa.sector_id
                JOIN users u ON u.user_id = sa.user_id
                LEFT JOIN staff st ON st.user_id = sa.user_id AND st.is_active = 1
                WHERE sa.designation_id = ? 
                AND sa.sector_id = ? 
                AND sa.is_active = 1
                ORDER BY d.department_name";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$programHeadDesignationId, $sectorId]);
        $programHeads = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'program_heads' => $programHeads,
            'sector_id' => $sectorId
        ]);
        
    } else {
        // Get all Program Head assignments
        $sql = "SELECT 
                    sa.assignment_id,
                    sa.user_id,
                    sa.department_id,
                    sa.sector_id,
                    d.department_name,
                    s.sector_name,
                    CONCAT(u.first_name, ' ', u.last_name) AS full_name,
                    u.first_name,
                    u.last_name,
                    st.employee_number
                FROM signatory_assignments sa
                JOIN departments d ON d.department_id = sa.department_id
                JOIN sectors s ON s.sector_id = sa.sector_id
                JOIN users u ON u.user_id = sa.user_id
                LEFT JOIN staff st ON st.user_id = sa.user_id AND st.is_active = 1
                WHERE sa.designation_id = ? 
                AND sa.is_active = 1
                ORDER BY s.sector_name, d.department_name";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$programHeadDesignationId]);
        $programHeads = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Group by sector
        $groupedBySector = [];
        foreach ($programHeads as $ph) {
            $sectorName = $ph['sector_name'];
            if (!isset($groupedBySector[$sectorName])) {
                $groupedBySector[$sectorName] = [];
            }
            $groupedBySector[$sectorName][] = $ph;
        }
        
        echo json_encode([
            'success' => true,
            'program_heads' => $programHeads,
            'grouped_by_sector' => $groupedBySector
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
