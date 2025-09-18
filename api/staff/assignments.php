<?php
// -----------------------------------------------------------------------------
// Staff Department Assignments API
// Methods: GET, POST, PUT, DELETE - Manage staff-department assignments
// -----------------------------------------------------------------------------
// GET: Fetch assignments for a specific staff member
// POST: Create new assignment
// PUT: Update assignment (e.g., change primary status)
// DELETE: Remove assignment
// -----------------------------------------------------------------------------

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            handleGetAssignments($pdo);
            break;
        case 'POST':
            handleCreateAssignment($pdo, $auth);
            break;
        case 'PUT':
            handleUpdateAssignment($pdo, $auth);
            break;
        case 'DELETE':
            handleDeleteAssignment($pdo, $auth);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

function handleGetAssignments($pdo) {
    $staffId = $_GET['staff_id'] ?? null;
    $departmentId = $_GET['department_id'] ?? null;
    $sectorId = $_GET['sector_id'] ?? null;
    
    if (!$staffId && !$departmentId && !$sectorId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'At least one filter parameter is required']);
        return;
    }
    
    $where = ['sda.is_active = 1'];
    $params = [];
    
    if ($staffId) {
        $where[] = 'sda.staff_id = :staff_id';
        $params[':staff_id'] = $staffId;
    }
    
    if ($departmentId) {
        $where[] = 'sda.department_id = :department_id';
        $params[':department_id'] = $departmentId;
    }
    
    if ($sectorId) {
        $where[] = 'sda.sector_id = :sector_id';
        $params[':sector_id'] = $sectorId;
    }
    
    $sql = "SELECT sda.assignment_id, sda.staff_id, sda.department_id, sda.sector_id, 
                   sda.is_primary, sda.assigned_at, sda.assigned_by,
                   s.staff_category, u.first_name, u.last_name, u.username,
                   d.department_name, d.department_code, d.department_type,
                   sec.sector_name
            FROM staff_department_assignments sda
            JOIN staff s ON sda.staff_id = s.employee_number
            JOIN users u ON s.user_id = u.user_id
            JOIN departments d ON sda.department_id = d.department_id
            JOIN sectors sec ON sda.sector_id = sec.sector_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY sda.is_primary DESC, d.department_name ASC";
    
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'assignments' => $assignments
    ]);
}

function handleCreateAssignment($pdo, $auth) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON body']);
        return;
    }
    
    $staffId = $input['staff_id'] ?? null;
    $departmentId = $input['department_id'] ?? null;
    $sectorId = $input['sector_id'] ?? null;
    $isPrimary = $input['is_primary'] ?? false;
    
    if (!$staffId || !$departmentId || !$sectorId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'staff_id, department_id, and sector_id are required']);
        return;
    }
    
    // Validate staff is a Program Head
    $stmt = $pdo->prepare("SELECT staff_category FROM staff WHERE employee_number = ?");
    $stmt->execute([$staffId]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$staff || $staff['staff_category'] !== 'Program Head') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Only Program Heads can be assigned to departments']);
        return;
    }
    
    // Check if assignment already exists
    $stmt = $pdo->prepare("SELECT assignment_id FROM staff_department_assignments WHERE staff_id = ? AND department_id = ? AND is_active = 1");
    $stmt->execute([$staffId, $departmentId]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Assignment already exists']);
        return;
    }
    
    // If setting as primary, unset other primary assignments for this staff
    if ($isPrimary) {
        $stmt = $pdo->prepare("UPDATE staff_department_assignments SET is_primary = 0 WHERE staff_id = ? AND is_active = 1");
        $stmt->execute([$staffId]);
    }
    
    // Create new assignment
    $stmt = $pdo->prepare("INSERT INTO staff_department_assignments (staff_id, department_id, sector_id, is_primary, assigned_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$staffId, $departmentId, $sectorId, $isPrimary ? 1 : 0, $auth->getUserId()]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Assignment created successfully',
        'assignment_id' => $pdo->lastInsertId()
    ]);
}

function handleUpdateAssignment($pdo, $auth) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON body']);
        return;
    }
    
    $assignmentId = $input['assignment_id'] ?? null;
    $isPrimary = $input['is_primary'] ?? null;
    
    if (!$assignmentId || $isPrimary === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'assignment_id and is_primary are required']);
        return;
    }
    
    // Get current assignment
    $stmt = $pdo->prepare("SELECT staff_id FROM staff_department_assignments WHERE assignment_id = ? AND is_active = 1");
    $stmt->execute([$assignmentId]);
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$assignment) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Assignment not found']);
        return;
    }
    
    // If setting as primary, unset other primary assignments for this staff
    if ($isPrimary) {
        $stmt = $pdo->prepare("UPDATE staff_department_assignments SET is_primary = 0 WHERE staff_id = ? AND assignment_id != ? AND is_active = 1");
        $stmt->execute([$assignment['staff_id'], $assignmentId]);
    }
    
    // Update assignment
    $stmt = $pdo->prepare("UPDATE staff_department_assignments SET is_primary = ?, assigned_by = ? WHERE assignment_id = ?");
    $stmt->execute([$isPrimary ? 1 : 0, $auth->getUserId(), $assignmentId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Assignment updated successfully'
    ]);
}

function handleDeleteAssignment($pdo, $auth) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON body']);
        return;
    }
    
    $assignmentId = $input['assignment_id'] ?? null;
    $staffId = $input['staff_id'] ?? null;
    $departmentId = $input['department_id'] ?? null;
    
    if (!$assignmentId && (!$staffId || !$departmentId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'assignment_id or (staff_id and department_id) are required']);
        return;
    }
    
    if ($assignmentId) {
        // Delete by assignment ID
        $stmt = $pdo->prepare("UPDATE staff_department_assignments SET is_active = 0 WHERE assignment_id = ?");
        $stmt->execute([$assignmentId]);
    } else {
        // Delete by staff_id and department_id
        $stmt = $pdo->prepare("UPDATE staff_department_assignments SET is_active = 0 WHERE staff_id = ? AND department_id = ? AND is_active = 1");
        $stmt->execute([$staffId, $departmentId]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Assignment removed successfully'
    ]);
}
?>
