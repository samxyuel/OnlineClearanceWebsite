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
    $userId = $_GET['user_id'] ?? null;
    $departmentId = $_GET['department_id'] ?? null;
    
    // Support both staff_id (legacy) and user_id (new)
    $targetUserId = $userId ?? $staffId;
    
    if (!$targetUserId && !$departmentId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'At least one filter parameter is required']);
        return;
    }
    
    $where = ['uda.is_active = 1'];
    $params = [];
    
    if ($targetUserId) {
        $where[] = 'uda.user_id = :user_id';
        $params[':user_id'] = $targetUserId;
    }
    
    if ($departmentId) {
        $where[] = 'uda.department_id = :department_id';
        $params[':department_id'] = $departmentId;
    }
    
    $sql = "SELECT uda.user_id, uda.department_id, 
                   uda.is_primary, uda.created_at,
                   u.first_name, u.last_name, u.username,
                   d.department_name, d.department_code, d.department_type,
                   COALESCE(sec.sector_name, d.department_type) as sector_name
            FROM user_department_assignments uda
            JOIN users u ON uda.user_id = u.user_id
            JOIN departments d ON uda.department_id = d.department_id
            LEFT JOIN sectors sec ON d.sector_id = sec.sector_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY uda.is_primary DESC, d.department_name ASC";
    
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
        // Fallback for non-JSON form data if needed, but we expect JSON
        $input = $_POST;
    }
    
    $staffId = $input['staff_id'] ?? null;
    $userId = $input['user_id'] ?? null;
    $departmentId = $input['department_id'] ?? null;
    $isPrimary = $input['is_primary'] ?? false;
    
    // Support both staff_id (legacy) and user_id (new)
    $targetUserId = $userId ?? $staffId;
    
    if (!$targetUserId || !$departmentId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'user_id and department_id are required']);
        return;
    }
    
    // Check if assignment already exists
    $stmt = $pdo->prepare("SELECT 1 FROM user_department_assignments WHERE user_id = ? AND department_id = ?");
    $stmt->execute([$targetUserId, $departmentId]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Assignment already exists']);
        return;
    }
    
    // If setting as primary, unset other primary assignments for this user
    if ($isPrimary) {
        $stmt = $pdo->prepare("UPDATE user_department_assignments SET is_primary = 0 WHERE user_id = ? AND is_active = 1");
        $stmt->execute([$targetUserId]);
    }
    
    // Create new assignment
    $stmt = $pdo->prepare("INSERT INTO user_department_assignments (user_id, department_id, is_primary, is_active) VALUES (?, ?, ?, 1)");
    $stmt->execute([$targetUserId, $departmentId, $isPrimary ? 1 : 0]);
    
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
    
    $userId = $input['user_id'] ?? null;
    $departmentId = $input['department_id'] ?? null;
    $isPrimary = $input['is_primary'] ?? null;
    
    if (!$userId || !$departmentId || $isPrimary === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'user_id, department_id and is_primary are required']);
        return;
    }
    
    // Get current assignment
    $stmt = $pdo->prepare("SELECT 1 FROM user_department_assignments WHERE user_id = ? AND department_id = ? AND is_active = 1");
    $stmt->execute([$userId, $departmentId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Assignment not found']);
        return;
    }
    
    // If setting as primary, unset other primary assignments for this user
    if ($isPrimary) {
        $stmt = $pdo->prepare("UPDATE user_department_assignments SET is_primary = 0 WHERE user_id = ? AND department_id != ? AND is_active = 1");
        $stmt->execute([$userId, $departmentId]);
    }
    
    // Update assignment
    $stmt = $pdo->prepare("UPDATE user_department_assignments SET is_primary = ? WHERE user_id = ? AND department_id = ?");
    $stmt->execute([$isPrimary ? 1 : 0, $userId, $departmentId]);
    
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
    
    $userId = $input['user_id'] ?? $input['staff_id'] ?? null;
    $departmentId = $input['department_id'] ?? null;
    
    if (!$userId || !$departmentId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'user_id and department_id are required']);
        return;
    }
    
    // Delete by user_id and department_id
    $stmt = $pdo->prepare("DELETE FROM user_department_assignments WHERE user_id = ? AND department_id = ?");
    $stmt->execute([$userId, $departmentId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Assignment removed successfully'
    ]);
}
?>
