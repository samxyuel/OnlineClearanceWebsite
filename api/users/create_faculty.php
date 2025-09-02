<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../includes/classes/UserManager.php';
require_once '../../includes/classes/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Authentication required']);
    exit;
}
if (!$auth->hasPermission('create_users')) {
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'Insufficient permissions']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!$payload) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Invalid JSON body']);
    exit;
}

$userManager = new UserManager();
$result = $userManager->createFaculty($payload);

http_response_code($result['success'] ? 201 : 400);
echo json_encode($result);
