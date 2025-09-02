<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$userManager = new UserManager();

// Get query parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$search = $_GET['search'] ?? '';
$role_id = $_GET['role_id'] ?? '';
$status = $_GET['status'] ?? '';

// Validate parameters
if ($page < 1) $page = 1;
if ($limit < 1 || $limit > 100) $limit = 20;

// Build filters
$filters = [];
if (!empty($search)) $filters['search'] = $search;
if (!empty($role_id)) $filters['role_id'] = $role_id;
if (!empty($status)) $filters['status'] = $status;

// Check if requesting specific user
if (isset($_GET['user_id'])) {
    $userId = (int)$_GET['user_id'];
    $user = $userManager->getUserById($userId);
    
    if ($user) {
        // Remove sensitive information
        unset($user['password']);
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
    exit;
}

// Get all users
$result = $userManager->getAllUsers($page, $limit, $filters);

if ($result['success']) {
    // Remove sensitive information from all users
    foreach ($result['users'] as &$user) {
        unset($user['password']);
    }
    
    echo json_encode($result);
} else {
    http_response_code(500);
    echo json_encode($result);
}
?>
