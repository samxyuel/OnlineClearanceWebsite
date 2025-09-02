<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(200);exit;}

require_once '../../includes/classes/Auth.php';
require_once '../../includes/classes/UserManager.php';
$auth=new Auth();
if(!$auth->isLoggedIn()){
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Auth required']);
    exit;
}
if(!$auth->hasPermission('delete_users')){
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'Forbidden']);
    exit;
}
$payload=json_decode(file_get_contents('php://input'),true);
if(!$payload||empty($payload['employee_number'])){
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'employee_number required']);
    exit;
}
$employeeId=$payload['employee_number'];
$um=new UserManager();
$result=$um->deleteFaculty($employeeId);
http_response_code($result['success']?200:400);
echo json_encode($result);
