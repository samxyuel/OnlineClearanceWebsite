<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../includes/classes/Auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$username = $input['username'] ?? '';
$password = $input['password'] ?? '';

if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Username and password are required']);
    exit;
}

// Authenticate user
$auth = new Auth();
$result = $auth->authenticate($username, $password);

// Always return 200 OK - use JSON body to indicate success/failure
// This prevents browser from showing red errors for business logic failures
// HTTP error codes (401, 404, 500) should be reserved for actual HTTP errors
http_response_code(200);
echo json_encode($result);
?>

