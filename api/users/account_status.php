<?php
/**
 * Unified Account Status Management API Endpoint
 * 
 * Handles account status changes (activate/deactivate) for all user types
 * Supports both single and bulk operations
 * 
 * Request Method: POST
 * 
 * Request Body (JSON):
 * {
 *     "action": "activate" | "deactivate",    // Required
 *     "user_id": 123,                          // For single operation
 *     "user_ids": [1, 2, 3]                    // For bulk operation
 * }
 * 
 * Response:
 * {
 *     "success": true/false,
 *     "message": "Status message",
 *     "affected_count": 5                      // Number of users updated
 * }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../includes/classes/UserManager.php';
require_once '../../includes/classes/Auth.php';
require_once '../../includes/config/database.php';

// Check if user is authenticated
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Check permission (admin or program head can manage status)
$hasPermission = $auth->hasPermission('edit_users') || $auth->getRoleName() === 'Program Head';
if (!$hasPermission) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
    exit;
}

// Only accept POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

// Validate action
if (empty($input['action']) || !in_array($input['action'], ['activate', 'deactivate'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action. Must be "activate" or "deactivate"']);
    exit;
}

$action = $input['action'];
$newStatus = ($action === 'activate') ? 'active' : 'inactive';

// Determine if this is single or bulk operation
$userIds = [];
if (!empty($input['user_ids']) && is_array($input['user_ids'])) {
    // Bulk operation
    $userIds = array_map('intval', $input['user_ids']);
} elseif (!empty($input['user_id'])) {
    // Single operation
    $userIds = [(int)$input['user_id']];
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID(s) required']);
    exit;
}

if (empty($userIds)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No valid user IDs provided']);
    exit;
}

try {
    $pdo = Database::getInstance()->getConnection();
    
    // For Program Heads, verify they can only manage users in their departments
    $roleName = $auth->getRoleName();
    if ($roleName === 'Program Head' && $roleName !== 'Admin') {
        require_once '../../includes/helpers/department_helpers.php';
        $programHeadDepartments = getCrossSectorDepartmentIds($pdo, $auth->getUserId());
        
        if (!empty($programHeadDepartments)) {
            // Verify all user_ids belong to students in their departments
            $placeholders = implode(',', array_fill(0, count($userIds), '?'));
            $deptPlaceholders = implode(',', array_fill(0, count($programHeadDepartments), '?'));
            
            $checkStmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM student_information si
                WHERE si.user_id IN ($placeholders)
                AND si.department_id IN ($deptPlaceholders)
            ");
            
            $checkStmt->execute(array_merge($userIds, $programHeadDepartments));
            $validCount = $checkStmt->fetchColumn();
            
            if ($validCount != count($userIds)) {
                http_response_code(403);
                echo json_encode([
                    'success' => false, 
                    'message' => 'You can only manage students in your assigned departments'
                ]);
                exit;
            }
        }
    }
    
    // Prevent changing admin user status (protect system admin)
    $placeholders = implode(',', array_fill(0, count($userIds), '?'));
    $adminCheckStmt = $pdo->prepare("
        SELECT u.user_id 
        FROM users u
        JOIN user_roles ur ON u.user_id = ur.user_id
        JOIN roles r ON ur.role_id = r.role_id
        WHERE u.user_id IN ($placeholders) 
        AND r.role_name = 'Admin'
    ");
    $adminCheckStmt->execute($userIds);
    $adminUsers = $adminCheckStmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($adminUsers)) {
        http_response_code(403);
        echo json_encode([
            'success' => false, 
            'message' => 'Cannot change status of administrator accounts'
        ]);
        exit;
    }
    
    // Update account status
    $updateStmt = $pdo->prepare("
        UPDATE users 
        SET account_status = ? 
        WHERE user_id IN ($placeholders)
    ");
    
    $params = array_merge([$newStatus], $userIds);
    $updateStmt->execute($params);
    
    $affectedCount = $updateStmt->rowCount();
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => "Successfully {$action}d {$affectedCount} user(s)",
        'affected_count' => $affectedCount
    ]);
    
} catch (PDOException $e) {
    error_log("Account Status Update Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred while updating account status'
    ]);
}
