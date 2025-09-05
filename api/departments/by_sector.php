<?php
// -----------------------------------------------------------------------------
// Departments by Sector API
// Method: GET - Get departments grouped by sector
// -----------------------------------------------------------------------------
// Query Parameters:
//   - sector_id: Get departments for specific sector only
//   - include_program_head_info: Include Program Head assignment info
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
    $includeProgramHeadInfo = isset($_GET['include_program_head_info']) ? 
        filter_var($_GET['include_program_head_info'], FILTER_VALIDATE_BOOLEAN) : false;
    
    // Base query for departments
    $sql = "SELECT 
                d.department_id,
                d.department_name,
                d.department_code,
                d.department_type,
                d.sector_id,
                s.sector_name
            FROM departments d
            JOIN sectors s ON s.sector_id = d.sector_id
            WHERE d.is_active = 1";
    
    $params = [];
    
    if ($sectorId) {
        $sql .= " AND d.sector_id = ?";
        $params[] = $sectorId;
    }
    
    $sql .= " ORDER BY s.sector_name, d.department_name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If requested, include Program Head assignment info
    if ($includeProgramHeadInfo) {
        // Get Program Head designation ID
        $phStmt = $pdo->prepare("SELECT designation_id FROM designations WHERE designation_name = 'Program Head' AND is_active = 1 LIMIT 1");
        $phStmt->execute();
        $programHeadDesignationId = $phStmt->fetchColumn();
        
        if ($programHeadDesignationId) {
            // Get Program Head assignments
            $phSql = "SELECT 
                        sa.department_id,
                        CONCAT(u.first_name, ' ', u.last_name) AS program_head_name,
                        u.first_name,
                        u.last_name,
                        st.employee_number
                      FROM signatory_assignments sa
                      JOIN users u ON u.user_id = sa.user_id
                      LEFT JOIN staff st ON st.user_id = sa.user_id AND st.is_active = 1
                      WHERE sa.designation_id = ? 
                      AND sa.is_active = 1";
            
            $phStmt = $pdo->prepare($phSql);
            $phStmt->execute([$programHeadDesignationId]);
            $programHeadAssignments = $phStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Create lookup array
            $phLookup = [];
            foreach ($programHeadAssignments as $ph) {
                $phLookup[$ph['department_id']] = $ph;
            }
            
            // Add Program Head info to departments
            foreach ($departments as &$dept) {
                $dept['program_head'] = $phLookup[$dept['department_id']] ?? null;
                $dept['has_program_head'] = isset($phLookup[$dept['department_id']]);
            }
        }
    }
    
    // Group by sector
    $groupedBySector = [];
    foreach ($departments as $dept) {
        $sectorName = $dept['sector_name'];
        if (!isset($groupedBySector[$sectorName])) {
            $groupedBySector[$sectorName] = [
                'sector_id' => $dept['sector_id'],
                'sector_name' => $sectorName,
                'departments' => []
            ];
        }
        $groupedBySector[$sectorName]['departments'][] = $dept;
    }
    
    echo json_encode([
        'success' => true,
        'departments' => $departments,
        'grouped_by_sector' => array_values($groupedBySector),
        'total_departments' => count($departments)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
