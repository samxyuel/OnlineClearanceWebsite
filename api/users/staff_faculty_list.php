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

// Allow staff members to view faculty data (more permissive than admin-only faculty_list.php)
$userId = $auth->getUserId();
$pdo = Database::getInstance()->getConnection();

// Check if user is staff or has admin privileges
$staffCheck = $pdo->prepare("
    SELECT COUNT(*) 
    FROM staff s 
    WHERE s.user_id = ? AND s.is_active = 1
");
$staffCheck->execute([$userId]);
$isStaff = (int)$staffCheck->fetchColumn() > 0;

// If not staff, check if user has admin role
if (!$isStaff) {
    $roleCheck = $pdo->prepare("
        SELECT r.role_name 
        FROM users u 
        JOIN user_roles ur ON u.user_id = ur.user_id 
        JOIN roles r ON ur.role_id = r.role_id 
        WHERE u.user_id = ? AND r.role_name IN ('Admin', 'Program Head', 'School Administrator')
    ");
    $roleCheck->execute([$userId]);
    $hasAdminRole = $roleCheck->fetchColumn();
    
    if (!$hasAdminRole) {
        http_response_code(403);
        echo json_encode(['success'=>false,'message'=>'Staff access required']);
        exit;
    }
}

$page = isset($_GET['page'])?max(1,(int)$_GET['page']):1;
$limit = isset($_GET['limit'])?min(100,max(5,(int)$_GET['limit'])):50;
$offset = ($page-1)*$limit;
$search = $_GET['search']??'';

// Determine active academic year & semester (same logic as clearance APIs)
$activePeriodStmt = $pdo->query("
    SELECT academic_year_id, semester_id 
    FROM clearance_periods 
    WHERE is_active = 1 
    LIMIT 1
");
$activePeriod = $activePeriodStmt->fetch(PDO::FETCH_ASSOC);

if (!$activePeriod) {
    echo json_encode(['success'=>false,'message'=>'No active clearance period']);
    exit;
}

$ayId = $activePeriod['academic_year_id'];
$semId = $activePeriod['semester_id'];

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
        CASE 
            WHEN cf.clearance_form_id IS NULL THEN 'Unapplied'
            WHEN cf.status = 'Unapplied' THEN 'Unapplied'
            WHEN cf.status = 'Applied' OR cf.status = 'In Progress' THEN 'In Progress'
            WHEN cf.status = 'Complete' THEN 'Complete'
            WHEN cf.status = 'Incomplete' THEN 'Incomplete'
            ELSE 'Unapplied'
        END AS clearance_status";

$join="FROM faculty f JOIN users u ON u.user_id=f.user_id
        LEFT JOIN clearance_forms cf ON cf.user_id=u.user_id AND cf.academic_year_id = ? AND cf.semester_id = ?";

$sql="$select $join $where ORDER BY u.created_at DESC LIMIT $limit OFFSET $offset";
$stmt=$pdo->prepare($sql);
$stmt->execute(array_merge([$ayId,$semId],$params));
$rows=$stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success'=>true,'total'=>$total,'page'=>$page,'limit'=>$limit,'faculty'=>$rows]);
?>
