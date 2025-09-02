<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../includes/classes/UserManager.php';
require_once '../../includes/classes/Auth.php';

// Check if user is authenticated and has admin privileges
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

if (!$auth->hasPermission('delete_users')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get user ID from query string or request body
$userId = null;

// Try to get from query string first
if (isset($_GET['user_id'])) {
    $userId = (int)$_GET['user_id'];
} else {
    // Try to get from request body
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input && isset($input['user_id'])) {
        $userId = (int)$input['user_id'];
    }
}

if (!$userId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

// Delete user
$userManager = new UserManager();
$result = $userManager->deleteUser($userId);

if ($result['success']) {
    echo json_encode($result);
} else {
    http_response_code(400);
    echo json_encode($result);
}
?>
