<?php
// -----------------------------------------------------------------------------
// Sector Coverage API
// Method: GET - Get Program Head coverage by sector with detailed assignments
// -----------------------------------------------------------------------------
// Query Parameters:
//   - sector_id: Get coverage for specific sector (optional)
//   - include_assignments: Include detailed assignment information (default: true)
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
// Temporarily disable auth for testing
// if (!$auth->isLoggedIn()) {
//     http_response_code(401);
//     echo json_encode(['success' => false, 'message' => 'Authentication required']);
//     exit;
// }

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $pdo = Database::getInstance()->getConnection();
    
    $sectorId = $_GET['sector_id'] ?? null;
    $includeAssignments = $_GET['include_assignments'] ?? 'true';
    $includeAssignments = $includeAssignments === 'true' || $includeAssignments === '1';
    
    // Get all sectors or specific sector
    $sectorWhere = $sectorId ? 'WHERE s.sector_id = :sector_id' : '';
    $sectorParams = $sectorId ? [':sector_id' => $sectorId] : [];
    
    $sql = "SELECT s.sector_id, s.sector_name,
                   COUNT(DISTINCT d.department_id) as total_departments,
                   COUNT(DISTINCT uda.department_id) as assigned_departments,
                   COUNT(DISTINCT uda.user_id) as assigned_program_heads
            FROM sectors s
            LEFT JOIN departments d ON s.sector_id = d.sector_id AND d.is_active = 1
            LEFT JOIN user_department_assignments uda ON d.department_id = uda.department_id AND uda.is_active = 1
            $sectorWhere
            GROUP BY s.sector_id, s.sector_name
            ORDER BY s.sector_name";
    
    $stmt = $pdo->prepare($sql);
    foreach ($sectorParams as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $sectors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result = [
        'success' => true,
        'sectors' => []
    ];
    
    foreach ($sectors as $sector) {
        $sectorData = [
            'sector_id' => $sector['sector_id'],
            'sector_name' => $sector['sector_name'],
            'total_departments' => (int)$sector['total_departments'],
            'assigned_departments' => (int)$sector['assigned_departments'],
            'unassigned_departments' => (int)$sector['total_departments'] - (int)$sector['assigned_departments'],
            'assigned_program_heads' => (int)$sector['assigned_program_heads']
        ];
        
        if ($includeAssignments) {
            // Get Program Head assignments for this sector
            $assignmentsSql = "SELECT uda.user_id, uda.department_id, uda.is_primary,
                                      u.first_name, u.last_name, u.username,
                                      d.department_name, d.department_code
                               FROM user_department_assignments uda
                               JOIN users u ON uda.user_id = u.user_id
                               JOIN departments d ON uda.department_id = d.department_id
                               WHERE d.sector_id = :sector_id AND uda.is_active = 1
                               ORDER BY uda.is_primary DESC, u.last_name ASC, d.department_name ASC";
            
            $assignmentsStmt = $pdo->prepare($assignmentsSql);
            $assignmentsStmt->execute([':sector_id' => $sector['sector_id']]);
            $assignments = $assignmentsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Group assignments by Program Head
            $programHeads = [];
            foreach ($assignments as $assignment) {
                $userId = $assignment['user_id'];
                if (!isset($programHeads[$userId])) {
                    $programHeads[$userId] = [
                        'user_id' => $userId,
                        'name' => trim($assignment['first_name'] . ' ' . $assignment['last_name']),
                        'username' => $assignment['username'],
                        'departments' => [],
                        'primary_department' => null
                    ];
                }
                
                $dept = [
                    'department_id' => $assignment['department_id'],
                    'department_name' => $assignment['department_name'],
                    'department_code' => $assignment['department_code'],
                    'is_primary' => (bool)$assignment['is_primary']
                ];
                
                $programHeads[$userId]['departments'][] = $dept;
                
                if ($assignment['is_primary']) {
                    $programHeads[$userId]['primary_department'] = $dept;
                }
            }
            
            $sectorData['program_heads'] = array_values($programHeads);
            
            // Get unassigned departments
            $unassignedSql = "SELECT d.department_id, d.department_name, d.department_code
                              FROM departments d
                              LEFT JOIN user_department_assignments uda ON d.department_id = uda.department_id AND uda.is_active = 1
                              WHERE d.sector_id = :sector_id AND d.is_active = 1 AND uda.department_id IS NULL
                              ORDER BY d.department_name";
            
            $unassignedStmt = $pdo->prepare($unassignedSql);
            $unassignedStmt->execute([':sector_id' => $sector['sector_id']]);
            $sectorData['unassigned_departments'] = $unassignedStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        $result['sectors'][] = $sectorData;
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
