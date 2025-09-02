<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');

require_once '../../includes/config/database.php';
require_once '../../includes/classes/Auth.php';

$auth = new Auth();
if(!$auth->isLoggedIn()){
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Auth required']);
    exit;
}
if(!$auth->hasPermission('view_users')){
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'Forbidden']);
    exit;
}

$page = isset($_GET['page'])?max(1,(int)$_GET['page']):1;
$limit = isset($_GET['limit'])?min(100,max(5,(int)$_GET['limit'])):50;
$offset = ($page-1)*$limit;
$search = $_GET['search']??'';

$pdo = Database::getInstance()->getConnection();

// Determine active academic year & semester
$ayId = $pdo->query("SELECT academic_year_id FROM academic_years WHERE is_active=1 LIMIT 1")->fetchColumn();
$semStmt = $pdo->prepare("SELECT semester_id FROM semesters WHERE academic_year_id=? AND is_active=1 LIMIT 1");
$semStmt->execute([$ayId]);
$semId = $semStmt->fetchColumn();

$where="";
$params=[];
if($search!==''){
    $where = "WHERE (u.username LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR f.employee_number LIKE ?)";
    $s="%$search%"; $params=[$s,$s,$s,$s];
}

$totalStmt=$pdo->prepare("SELECT COUNT(*) FROM faculty f JOIN users u ON u.user_id=f.user_id $where");
$totalStmt->execute($params);
$total=$totalStmt->fetchColumn();

$select="SELECT f.employee_number, f.employment_status, u.user_id, u.username, u.first_name, u.last_name, u.status,
        COALESCE(NULLIF(cf.status,''),'Unapplied') AS clearance_status";

$join="FROM faculty f JOIN users u ON u.user_id=f.user_id
        LEFT JOIN clearance_forms cf ON cf.user_id=u.user_id AND cf.academic_year_id = ? AND cf.semester_id = ?";

$sql="$select $join $where ORDER BY u.created_at DESC LIMIT $limit OFFSET $offset";
$stmt=$pdo->prepare($sql);
$stmt->execute(array_merge([$ayId,$semId],$params));
$rows=$stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success'=>true,'total'=>$total,'page'=>$page,'limit'=>$limit,'faculty'=>$rows]);
