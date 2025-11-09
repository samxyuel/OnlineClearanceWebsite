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
        // For Faculty sector, use department_type = 'Faculty' or sector_id = 3
        if ($expectedSectorName === 'Faculty') {
            $sql = "
                SELECT 
                    d.department_id,
                    d.department_name,
                    s.sector_name
                FROM departments d
                JOIN sectors s ON d.sector_id = s.sector_id
                WHERE d.is_active = 1 
                    AND (d.department_type = 'Faculty' OR d.sector_id = 3)
                ORDER BY d.department_name ASC
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
        } else {
            // For College and SHS sectors, use sector_name
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
        }
        
        $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } else {
        // Program Head: Get their assigned department from the staff table
        // The staff.department_id column contains the Program Head's assigned department
        $staffStmt = $pdo->prepare("
            SELECT 
                s.employee_number, 
                s.staff_category,
                s.department_id
            FROM staff s 
            WHERE s.user_id = ? 
                AND s.staff_category = 'Program Head' 
                AND s.is_active = 1
            LIMIT 1
        ");
        $staffStmt->execute([$userId]);
        $staff = $staffStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$staff) {
            // Try alternative query - maybe staff_category is different
            $altStmt = $pdo->prepare("
                SELECT 
                    s.employee_number, 
                    s.staff_category,
                    s.department_id
                FROM staff s 
                WHERE s.user_id = ? AND s.is_active = 1
                LIMIT 1
            ");
            $altStmt->execute([$userId]);
            $altStaff = $altStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$altStaff) {
                echo json_encode([
                    'success' => true,
                    'departments' => [],
                    'message' => 'Program Head staff record not found',
                    'debug' => ['user_id' => $userId, 'expected_sector' => $expectedSectorName]
                ]);
                return;
            }
            
            // Use alternative staff record if found
            $staff = $altStaff;
        }
        
        // Check if Program Head has a department assigned
        if (empty($staff['department_id'])) {
            echo json_encode([
                'success' => true,
                'departments' => [],
                'message' => 'Program Head does not have a department assigned',
                'debug' => [
                    'user_id' => $userId,
                    'employee_number' => $staff['employee_number'],
                    'staff_category' => $staff['staff_category'],
                    'department_id' => $staff['department_id']
                ]
            ]);
            return;
        }
        
        // Get the assigned department and verify it matches the expected sector
        // For Faculty sector, check department_type = 'Faculty' or sector_id = 3
        if ($expectedSectorName === 'Faculty') {
            $sql = "
                SELECT 
                    d.department_id,
                    d.department_name,
                    s.sector_name
                FROM departments d
                JOIN sectors s ON d.sector_id = s.sector_id
                WHERE d.department_id = ? 
                    AND d.is_active = 1
                    AND (d.department_type = 'Faculty' OR d.sector_id = 3)
                LIMIT 1
            ";
        } else {
            // For College and SHS sectors, use sector_name
            $sql = "
                SELECT 
                    d.department_id,
                    d.department_name,
                    s.sector_name
                FROM departments d
                JOIN sectors s ON d.sector_id = s.sector_id
                WHERE d.department_id = ? 
                    AND d.is_active = 1
                    AND s.sector_name = ?
                LIMIT 1
            ";
        }
        
        if ($expectedSectorName === 'Faculty') {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$staff['department_id']]);
        } else {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$staff['department_id'], $expectedSectorName]);
        }
        
        $department = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If department not found or doesn't match sector, return debug info
        if (!$department) {
            // Check what sector the assigned department actually belongs to
            $checkStmt = $pdo->prepare("
                SELECT 
                    d.department_id,
                    d.department_name,
                    d.department_type,
                    s.sector_name,
                    s.sector_id
                FROM departments d
                JOIN sectors s ON d.sector_id = s.sector_id
                WHERE d.department_id = ? AND d.is_active = 1
            ");
            $checkStmt->execute([$staff['department_id']]);
            $actualDepartment = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            // Get all available sector names for comparison
            $sectorStmt = $pdo->prepare("SELECT sector_name FROM sectors ORDER BY sector_name");
            $sectorStmt->execute();
            $allSectors = $sectorStmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo json_encode([
                'success' => true,
                'departments' => [],
                'message' => 'Program Head\'s assigned department does not match the expected sector',
                'debug' => [
                    'user_id' => $userId,
                    'employee_number' => $staff['employee_number'],
                    'assigned_department_id' => $staff['department_id'],
                    'assigned_department' => $actualDepartment,
                    'expected_sector' => $expectedSectorName,
                    'available_sectors' => $allSectors,
                    'sector_match' => in_array($expectedSectorName, $allSectors)
                ]
            ]);
            return;
        }
        
        // Return the single assigned department
        $departments = [$department];
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
        // Get staff record with department_id for this user
        $staffStmt = $pdo->prepare("
            SELECT 
                s.employee_number,
                s.department_id
            FROM staff s 
            WHERE s.user_id = ? 
                AND s.staff_category = 'Program Head' 
                AND s.is_active = 1
            LIMIT 1
        ");
        $staffStmt->execute([$userId]);
        $staff = $staffStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$staff) {
            // Try alternative query - maybe staff_category is different
            $altStmt = $pdo->prepare("
                SELECT 
                    s.employee_number,
                    s.department_id
                FROM staff s 
                WHERE s.user_id = ? AND s.is_active = 1
                LIMIT 1
            ");
            $altStmt->execute([$userId]);
            $altStaff = $altStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$altStaff) {
                throw new Exception('Program Head not found or inactive');
            }
            
            $staff = $altStaff;
        }
        
        // Verify that the requested department matches the Program Head's assigned department
        if (empty($staff['department_id'])) {
            throw new Exception('Program Head does not have a department assigned');
        }
        
        if ((int)$staff['department_id'] !== (int)$departmentId) {
            throw new Exception('Program Head does not have access to this department. Assigned department: ' . $staff['department_id']);
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
