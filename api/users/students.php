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

// Allow staff members to view student data
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
$type = $_GET['type']??''; // 'college' or 'senior_high'

// Try to determine active academic year & semester (for clearance status)
$activePeriodStmt = $pdo->query("
    SELECT academic_year_id, semester_id 
    FROM clearance_periods 
    WHERE is_active = 1 
    LIMIT 1
");
$activePeriod = $activePeriodStmt->fetch(PDO::FETCH_ASSOC);

// Default to null if no active period (clearance status will show as 'Unapplied')
$ayId = $activePeriod ? $activePeriod['academic_year_id'] : null;
$semId = $activePeriod ? $activePeriod['semester_id'] : null;

// Build where clause based on student type
$where = ["u.status IN ('active', 'inactive', 'graduated')"];
$params = [];

if ($type === 'college') {
    // College students - exclude Senior High School
    $where[] = "u.sector_id != (SELECT sector_id FROM sectors WHERE sector_name = 'Senior High School')";
} elseif ($type === 'senior_high') {
    // Senior High School students only
    $where[] = "u.sector_id = (SELECT sector_id FROM sectors WHERE sector_name = 'Senior High School')";
}

if($search !== ''){
    $where[] = "(u.username LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
    $s="%$search%"; 
    $params = array_merge($params, [$s,$s,$s]);
}

$whereSql = "WHERE " . implode(" AND ", $where);

$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM users u $whereSql");
$totalStmt->execute($params);
$total = $totalStmt->fetchColumn();

$select = "SELECT u.user_id, u.username, u.first_name, u.last_name, u.middle_name, u.status,
        CASE 
            WHEN cf.clearance_form_id IS NULL THEN 'Unapplied'
            WHEN cf.status = 'Unapplied' THEN 'Unapplied'
            WHEN cf.status = 'Applied' OR cf.status = 'In Progress' THEN 'In Progress'
            WHEN cf.status = 'Complete' THEN 'Complete'
            WHEN cf.status = 'Incomplete' THEN 'Incomplete'
            ELSE 'Unapplied'
        END AS clearance_status,
        u.program, u.year_level, u.section, u.strand, u.grade_level";

// Build join condition based on whether we have active period
if ($ayId && $semId) {
    $join = "FROM users u
            LEFT JOIN clearance_forms cf ON cf.user_id = u.user_id AND cf.academic_year_id = ? AND cf.semester_id = ?";
    $joinParams = [$ayId, $semId];
} else {
    // No active period - don't try to join clearance_forms (all will show as 'Unapplied')
    $join = "FROM users u
            LEFT JOIN clearance_forms cf ON 1=0"; // Never join - all clearance_status will be 'Unapplied'
    $joinParams = [];
}

$sql = "$select $join $whereSql ORDER BY u.created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute(array_merge($joinParams, $params));
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success'=>true,'total'=>$total,'page'=>$page,'limit'=>$limit,'students'=>$rows]);
?>
