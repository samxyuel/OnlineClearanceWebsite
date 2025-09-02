<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../includes/classes/Auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check if user is logged in
$auth = new Auth();

if ($auth->isLoggedIn()) {
    $user = $auth->getCurrentUser();
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'authenticated' => true,
        'user' => $user
    ]);
} else {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'authenticated' => false,
        'message' => 'User not authenticated'
    ]);
}
?>

