<?php
// Reset selected faculty clearance to Unapplied for current period
require_once '../../includes/config/database.php';
require_once '../../includes/classes/Auth.php';
header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD']!=='POST'){
    http_response_code(405);echo json_encode(['success'=>false,'message'=>'Method not allowed']);exit;
}

session_start();
$auth = new Auth();
if(!$auth->isLoggedIn()){
    http_response_code(403);echo json_encode(['success'=>false,'message'=>'Unauthorized']);exit;
}
$role = $auth->getRoleName();
if($role!=='Admin' && !$auth->hasPermission('reset_clearance')){
    http_response_code(403);echo json_encode(['success'=>false,'message'=>'Unauthorized']);exit;
}

$payload=json_decode(file_get_contents('php://input'),true);
$empIds=$payload['employee_numbers']??($payload['employee_ids']??[]);
if(empty($empIds) || !is_array($empIds)){
    http_response_code(400);echo json_encode(['success'=>false,'message'=>'employee_ids required']);exit;
}

try{
    $db = Database::getInstance()->getConnection();
    // fetch active period's AY and semester
    $stmt=$db->query("SELECT academic_year_id, semester_id FROM clearance_periods WHERE is_active=1 LIMIT 1");
    $period=$stmt->fetch(PDO::FETCH_ASSOC);
    if(!$period){throw new Exception('No active clearance period');}
    $ayId=$period['academic_year_id'];
    $semId=$period['semester_id'];

    $db->beginTransaction();
    // Get user_ids from employee ids
    $in=implode(',',array_fill(0,count($empIds),'?'));
    $userStmt=$db->prepare("SELECT employee_number, user_id FROM faculty WHERE employee_number IN ($in)");
    $userStmt->execute($empIds);
    $map=$userStmt->fetchAll(PDO::FETCH_KEY_PAIR); // employee_number => user_id
    if(!$map){throw new Exception('No matching faculty');}
    $userIds=array_values($map);

    $inUsers=implode(',',array_fill(0,count($userIds),'?'));
    // Find forms for users in current period (ay+semester)
    $formStmt=$db->prepare("SELECT clearance_form_id FROM clearance_forms WHERE user_id IN ($inUsers) AND academic_year_id=? AND semester_id=?");
    $formStmt->execute([...$userIds,$ayId,$semId]);
    $formIds=$formStmt->fetchAll(PDO::FETCH_COLUMN);
    if($formIds){
        $inForms=implode(',',array_fill(0,count($formIds),'?'));
        // reset signatories
        $db->prepare("UPDATE clearance_signatories SET action=NULL, remarks=NULL, updated_at=NULL WHERE clearance_form_id IN ($inForms)")->execute($formIds);
        // reset forms status
        $db->prepare("UPDATE clearance_forms SET status='Unapplied' WHERE clearance_form_id IN ($inForms)")->execute($formIds);
    }
    $db->commit();
    echo json_encode(['success'=>true,'reset_count'=>count($formIds)]);
}catch(Exception $e){
    if($db->inTransaction())$db->rollBack();
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
