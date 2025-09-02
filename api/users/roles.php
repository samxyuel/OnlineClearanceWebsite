<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../includes/classes/UserManager.php';
require_once '../../includes/classes/Auth.php';

// Check if user is authenticated
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$userManager = new UserManager();

// Handle different HTTP methods
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Get all available roles
        $roles = $userManager->getAllRoles();
        echo json_encode(['success' => true, 'roles' => $roles]);
        break;
        
    case 'POST':
        // Assign role to user
        if (!$auth->hasPermission('edit_users')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || empty($input['user_id']) || empty($input['role_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'User ID and Role ID are required']);
            exit;
        }
        
        $result = $userManager->assignRole($input['user_id'], $input['role_id']);
        
        if ($result['success']) {
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode($result);
        }
        break;
        
    case 'PUT':
        // Update user role
        if (!$auth->hasPermission('edit_users')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || empty($input['user_id']) || empty($input['role_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'User ID and Role ID are required']);
            exit;
        }
        
        $result = $userManager->updateUserRole($input['user_id'], $input['role_id']);
        
        if ($result['success']) {
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode($result);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
?>
