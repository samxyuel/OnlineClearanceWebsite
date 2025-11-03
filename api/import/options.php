<?php
/**
 * API: Import Modal Options
 * 
 * Unified endpoint for Import Modal to fetch departments and programs
 * with role-based filtering for bulk data import.
 * 
 * Query Parameters:
 *   - resource: 'departments' or 'programs'
 *   - pageType: 'college', 'shs', or 'faculty' (required for departments)
 *   - department_id: Required when resource='programs'
 * 
 * Response Format:
 *   Departments: { success: true, departments: [{department_id, department_name, sector_name}] }
 *   Programs: { success: true, programs: [{program_id, program_name, program_code}] }
 */

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

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Validate user role - Only Admin and Program Head can import
$userRoleName = $auth->getRoleName();
$allowedRoles = ['Admin', 'Program Head'];
if (!in_array($userRoleName, $allowedRoles)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Only Administrators and Program Heads can access import options']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$resource = $_GET['resource'] ?? '';
$pageType = $_GET['pageType'] ?? ''; // 'college', 'shs', 'faculty'
$departmentId = isset($_GET['department_id']) ? (int)$_GET['department_id'] : null;

try {
    $pdo = Database::getInstance()->getConnection();
    $userId = $auth->getUserId();

    switch ($resource) {
        case 'departments':
            handleGetDepartments($pdo, $userId, $userRoleName, $pageType);
            break;
        
        case 'programs':
            if (empty($departmentId)) {
                throw new Exception('department_id is required for programs');
            }
            handleGetPrograms($pdo, $userId, $userRoleName, $departmentId);
            break;
        
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid resource. Must be "departments" or "programs"']);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}

/**
 * Get departments filtered by sector and role
 */
function handleGetDepartments($pdo, $userId, $userRoleName, $pageType) {
    // Map pageType to sector name
    $sectorMap = [
        'college' => 'College',
        'shs' => 'Senior High School',
        'faculty' => 'Faculty'
    ];
    
    if (!isset($sectorMap[$pageType])) {
        throw new Exception('Invalid pageType. Must be "college", "shs", or "faculty"');
    }
    
    $expectedSectorName = $sectorMap[$pageType];
    
    if ($userRoleName === 'Admin') {
        // Admin sees all departments for the selected sector
        $sql = "
            SELECT 
                d.department_id,
                d.department_name,
                s.sector_name
            FROM departments d
            JOIN sectors s ON d.sector_id = s.sector_id
            WHERE d.is_active = 1 
                AND s.sector_name = ?
            ORDER BY d.department_name ASC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$expectedSectorName]);
        $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } else {
        // Program Head: Only their assigned departments that match the sector
        // Get staff employee_number for this user
        $staffStmt = $pdo->prepare("
            SELECT s.employee_number 
            FROM staff s 
            WHERE s.user_id = ? AND s.staff_category = 'Program Head' AND s.is_active = 1
            LIMIT 1
        ");
        $staffStmt->execute([$userId]);
        $staff = $staffStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$staff) {
            echo json_encode([
                'success' => true,
                'departments' => [],
                'message' => 'No assigned departments found for Program Head'
            ]);
            return;
        }
        
        // Get assigned departments from staff_department_assignments
        $sql = "
            SELECT 
                d.department_id,
                d.department_name,
                s.sector_name
            FROM staff_department_assignments sda
            JOIN departments d ON sda.department_id = d.department_id
            JOIN sectors s ON sda.sector_id = s.sector_id
            WHERE sda.staff_id = ? 
                AND sda.is_active = 1
                AND d.is_active = 1
                AND s.sector_name = ?
            ORDER BY d.department_name ASC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$staff['employee_number'], $expectedSectorName]);
        $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode([
        'success' => true,
        'departments' => $departments,
        'total' => count($departments)
    ]);
}

/**
 * Get programs filtered by department and role
 */
function handleGetPrograms($pdo, $userId, $userRoleName, $departmentId) {
    if ($userRoleName === 'Admin') {
        // Admin sees all programs for the selected department
        $sql = "
            SELECT 
                p.program_id,
                p.program_name,
                p.program_code,
                p.department_id
            FROM programs p
            WHERE p.is_active = 1 
                AND p.department_id = ?
            ORDER BY p.program_name ASC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$departmentId]);
        $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } else {
        // Program Head: Verify department is assigned to them, then get programs
        // Get staff employee_number for this user
        $staffStmt = $pdo->prepare("
            SELECT s.employee_number 
            FROM staff s 
            WHERE s.user_id = ? AND s.staff_category = 'Program Head' AND s.is_active = 1
            LIMIT 1
        ");
        $staffStmt->execute([$userId]);
        $staff = $staffStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$staff) {
            throw new Exception('Program Head not found or inactive');
        }
        
        // Verify department assignment
        $checkStmt = $pdo->prepare("
            SELECT 1 
            FROM staff_department_assignments sda
            WHERE sda.staff_id = ? 
                AND sda.department_id = ?
                AND sda.is_active = 1
            LIMIT 1
        ");
        $checkStmt->execute([$staff['employee_number'], $departmentId]);
        
        if (!$checkStmt->fetchColumn()) {
            throw new Exception('Program Head does not have access to this department');
        }
        
        // Get programs for the assigned department
        $sql = "
            SELECT 
                p.program_id,
                p.program_name,
                p.program_code,
                p.department_id
            FROM programs p
            WHERE p.is_active = 1 
                AND p.department_id = ?
            ORDER BY p.program_name ASC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$departmentId]);
        $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode([
        'success' => true,
        'programs' => $programs,
        'total' => count($programs)
    ]);
}

?>
