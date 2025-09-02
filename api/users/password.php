<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT');
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
    case 'POST':
        // Change password (user changing their own password)
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || empty($input['current_password']) || empty($input['new_password'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Current password and new password are required']);
            exit;
        }
        
        // Validate password strength
        if (strlen($input['new_password']) < 6) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters long']);
            exit;
        }
        
        $currentUser = $auth->getCurrentUser();
        $result = $userManager->changePassword($currentUser['user_id'], $input['current_password'], $input['new_password']);
        
        if ($result['success']) {
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode($result);
        }
        break;
        
    case 'PUT':
        // Reset password (admin resetting user's password)
        if (!$auth->hasPermission('reset_passwords')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || empty($input['user_id']) || empty($input['new_password'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'User ID and new password are required']);
            exit;
        }
        
        // Validate password strength
        if (strlen($input['new_password']) < 6) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters long']);
            exit;
        }
        
        $result = $userManager->resetPassword($input['user_id'], $input['new_password']);
        
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
