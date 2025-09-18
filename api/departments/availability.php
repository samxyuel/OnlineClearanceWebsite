<?php
/**
 * Department Availability API
 * Checks department availability for Program Head assignments
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../includes/config/database.php';

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// Validate sector
$validSectors = ['College', 'Senior High School', 'Faculty'];

function validateSector($sector) {
    global $validSectors;
    return in_array($sector, $validSectors);
}

function getDepartmentAvailability($pdo, $sector = null) {
    $sql = "
        SELECT 
            d.department_id,
            d.department_name,
            d.sector_id,
            s.sector_name,
            CASE 
                WHEN sda.department_id IS NOT NULL THEN 0
                ELSE 1
            END as is_available,
            sda.staff_id as assigned_staff_id,
            u.first_name as assigned_staff_first_name,
            u.last_name as assigned_staff_last_name,
            u.employee_number as assigned_staff_employee_number
        FROM departments d
        JOIN sectors s ON d.sector_id = s.sector_id
        LEFT JOIN staff_department_assignments sda ON d.department_id = sda.department_id AND sda.is_primary = 1
        LEFT JOIN users u ON sda.staff_id = u.user_id
    ";
    
    $params = [];
    if ($sector) {
        $sql .= " WHERE s.sector_name = ?";
        $params[] = $sector;
    }
    
    $sql .= " ORDER BY s.sector_name, d.department_name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAvailableDepartments($pdo, $sector) {
    $sql = "
        SELECT 
            d.department_id,
            d.department_name,
            d.sector_id,
            s.sector_name
        FROM departments d
        JOIN sectors s ON d.sector_id = s.sector_id
        LEFT JOIN staff_department_assignments sda ON d.department_id = sda.department_id AND sda.is_primary = 1
        WHERE s.sector_name = ? AND sda.department_id IS NULL
        ORDER BY d.department_name
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$sector]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAssignedDepartments($pdo, $sector) {
    $sql = "
        SELECT 
            d.department_id,
            d.department_name,
            d.sector_id,
            s.sector_name,
            sda.staff_id,
            u.first_name,
            u.last_name,
            u.employee_number
        FROM departments d
        JOIN sectors s ON d.sector_id = s.sector_id
        JOIN staff_department_assignments sda ON d.department_id = sda.department_id AND sda.is_primary = 1
        JOIN users u ON sda.staff_id = u.user_id
        WHERE s.sector_name = ?
        ORDER BY d.department_name
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$sector]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function checkSectorAvailability($pdo, $sector) {
    $sql = "
        SELECT 
            COUNT(*) as total_departments,
            SUM(CASE WHEN sda.department_id IS NOT NULL THEN 1 ELSE 0 END) as assigned_departments,
            SUM(CASE WHEN sda.department_id IS NULL THEN 1 ELSE 0 END) as available_departments
        FROM departments d
        JOIN sectors s ON d.sector_id = s.sector_id
        LEFT JOIN staff_department_assignments sda ON d.department_id = sda.department_id AND sda.is_primary = 1
        WHERE s.sector_name = ?
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$sector]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'total_departments' => (int)$result['total_departments'],
        'assigned_departments' => (int)$result['assigned_departments'],
        'available_departments' => (int)$result['available_departments'],
        'is_fully_assigned' => (int)$result['assigned_departments'] === (int)$result['total_departments'],
        'has_available_departments' => (int)$result['available_departments'] > 0
    ];
}

function getProgramHeadAvailability($pdo, $sector) {
    $sql = "
        SELECT 
            u.user_id,
            u.first_name,
            u.last_name,
            u.employee_number,
            COUNT(sda.department_id) as assigned_department_count,
            GROUP_CONCAT(d.department_name ORDER BY d.department_name SEPARATOR ', ') as assigned_departments
        FROM users u
        JOIN user_roles ur ON u.user_id = ur.user_id
        JOIN roles r ON ur.role_id = r.role_id
        LEFT JOIN staff_department_assignments sda ON u.user_id = sda.staff_id AND sda.is_primary = 1
        LEFT JOIN departments d ON sda.department_id = d.department_id
        LEFT JOIN sectors s ON d.sector_id = s.sector_id
        WHERE r.role_name = 'Program Head'
        AND (s.sector_name = ? OR s.sector_name IS NULL)
        GROUP BY u.user_id, u.first_name, u.last_name, u.employee_number
        ORDER BY u.first_name, u.last_name
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$sector]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function canRegisterProgramHead($pdo, $sector) {
    $availability = checkSectorAvailability($pdo, $sector);
    return $availability['has_available_departments'];
}

// Route handling
switch ($method) {
    case 'GET':
        $sector = $_GET['sector'] ?? null;
        $action = $_GET['action'] ?? null;
        
        if ($sector && !validateSector($sector)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid sector']);
            exit();
        }
        
        try {
            switch ($action) {
                case 'available':
                    if (!$sector) {
                        throw new Exception("Sector required for available departments");
                    }
                    $departments = getAvailableDepartments($pdo, $sector);
                    echo json_encode([
                        'success' => true,
                        'departments' => $departments,
                        'total' => count($departments)
                    ]);
                    break;
                    
                case 'assigned':
                    if (!$sector) {
                        throw new Exception("Sector required for assigned departments");
                    }
                    $departments = getAssignedDepartments($pdo, $sector);
                    echo json_encode([
                        'success' => true,
                        'departments' => $departments,
                        'total' => count($departments)
                    ]);
                    break;
                    
                case 'summary':
                    if (!$sector) {
                        throw new Exception("Sector required for summary");
                    }
                    $summary = checkSectorAvailability($pdo, $sector);
                    echo json_encode([
                        'success' => true,
                        'summary' => $summary
                    ]);
                    break;
                    
                case 'program_heads':
                    if (!$sector) {
                        throw new Exception("Sector required for program heads");
                    }
                    $programHeads = getProgramHeadAvailability($pdo, $sector);
                    echo json_encode([
                        'success' => true,
                        'program_heads' => $programHeads,
                        'total' => count($programHeads)
                    ]);
                    break;
                    
                case 'can_register_ph':
                    if (!$sector) {
                        throw new Exception("Sector required for Program Head registration check");
                    }
                    $canRegister = canRegisterProgramHead($pdo, $sector);
                    echo json_encode([
                        'success' => true,
                        'can_register_program_head' => $canRegister
                    ]);
                    break;
                    
                default:
                    $departments = getDepartmentAvailability($pdo, $sector);
                    echo json_encode([
                        'success' => true,
                        'departments' => $departments,
                        'total' => count($departments)
                    ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
?>
