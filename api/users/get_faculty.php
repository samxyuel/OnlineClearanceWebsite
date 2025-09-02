<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');

require_once '../../includes/config/database.php';
require_once '../../includes/classes/Auth.php';

$auth = new Auth();
if(!$auth->isLoggedIn()){
    http_response_code(401);echo json_encode(['success'=>false,'message'=>'Auth required']);exit;
}
if(!$auth->hasPermission('view_users')){
    http_response_code(403);echo json_encode(['success'=>false,'message'=>'Forbidden']);exit;
}

$employeeId = $_GET['employee_number'] ?? ($_GET['employee_id'] ?? '');
if($employeeId===''){http_response_code(400);echo json_encode(['success'=>false,'message'=>'employee_number required']);exit;}

$pdo = Database::getInstance()->getConnection();
$sql = "SELECT f.employee_number, f.employment_status, u.user_id, u.username, u.first_name, u.last_name, u.middle_name, u.email, u.contact_number, u.status
        FROM faculty f JOIN users u ON u.user_id = f.user_id WHERE f.employee_number = ? LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([$employeeId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$row){http_response_code(404);echo json_encode(['success'=>false,'message'=>'Faculty not found']);exit;}

echo json_encode(['success'=>true,'faculty'=>$row]);
