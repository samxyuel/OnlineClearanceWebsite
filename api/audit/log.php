<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../includes/functions/audit_functions.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

try{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success'=>false,'message'=>'Method not allowed']);
        exit;
    }

    $auth = new Auth();
    $userId = $auth->isLoggedIn() ? (int)$auth->getUserId() : 0;

    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?: [];
    $type = isset($data['activity_type']) ? trim((string)$data['activity_type']) : '';
    $details = isset($data['details']) && is_array($data['details']) ? $data['details'] : [];

    if ($type === '') {
        http_response_code(400);
        echo json_encode(['success'=>false,'message'=>'activity_type required']);
        exit;
    }

    // Fire-and-forget style; failures are swallowed by logActivity
    logActivity($userId, $type, $details);
    echo json_encode(['success'=>true]);
}catch(Throwable $e){
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error']);
}
?>


