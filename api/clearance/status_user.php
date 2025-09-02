<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}
if($_SERVER['REQUEST_METHOD']!=='GET'){http_response_code(405);echo json_encode(['success'=>false,'message'=>'Method not allowed']);exit;}

require_once '../../includes/classes/Auth.php';
require_once '../../includes/config/database.php';
$auth=new Auth();
if(!$auth->isLoggedIn()){http_response_code(401);echo json_encode(['success'=>false,'message'=>'Auth required']);exit;}
// Require view_clearance permission (assume admin roles have it)
if(!$auth->hasPermission('view_clearance')){http_response_code(403);echo json_encode(['success'=>false,'message'=>'Forbidden']);exit;}

$employeeId=isset($_GET['employee_number'])?trim($_GET['employee_number']):(isset($_GET['employee_id'])?trim($_GET['employee_id']):'');
$userIdParam=isset($_GET['user_id'])?intval($_GET['user_id']):0;
if($employeeId==='""' && !$userIdParam){http_response_code(400);echo json_encode(['success'=>false,'message'=>'employee_id or user_id required']);exit;}

try{
    $pdo=Database::getInstance()->getConnection();
    if($userIdParam){
        $userId=$userIdParam;
    }else{
        $uidStmt=$pdo->prepare("SELECT user_id FROM faculty WHERE employee_number=?");
        $uidStmt->execute([$employeeId]);
        $userId=$uidStmt->fetchColumn();
        if(!$userId){throw new Exception('Faculty not found');}
    }

    // Resolve applicant department (faculty or student)
    $deptId = null;
    try {
        $q1 = $pdo->prepare("SELECT department_id FROM faculty WHERE user_id=? LIMIT 1");
        $q1->execute([$userId]);
        $deptId = $q1->fetchColumn();
        if ($deptId === false || $deptId === null) {
            $q2 = $pdo->prepare("SELECT department_id FROM students WHERE user_id=? LIMIT 1");
            $q2->execute([$userId]);
            $deptId = $q2->fetchColumn();
        }
        if ($deptId !== false) { $deptId = $deptId ? (int)$deptId : null; }
    } catch (Exception $e) { /* ignore */ }

    // Same logic as status.php (simplified include but duplicated to avoid require)
    $ayId=$pdo->query("SELECT academic_year_id FROM academic_years WHERE is_active=1 LIMIT 1")->fetchColumn();
    $semId=$pdo->prepare("SELECT semester_id FROM semesters WHERE academic_year_id=? AND is_active=1 LIMIT 1");
    $semId->execute([$ayId]);
    $semId=$semId->fetchColumn();
    if(!$ayId||!$semId){echo json_encode(['success'=>true,'applied'=>false,'message'=>'No active period']);exit;}

    $formStmt=$pdo->prepare("SELECT clearance_form_id,status FROM clearance_forms WHERE user_id=? AND academic_year_id=? AND semester_id=? LIMIT 1");
    $formStmt->execute([$userId,$ayId,$semId]);
    $form=$formStmt->fetch(PDO::FETCH_ASSOC);
    if(!$form){echo json_encode(['success'=>true,'applied'=>false]);exit;}

    $sqlSig = "SELECT 
                    cs.designation_id,
                    d.designation_name,
                    cs.action,
                    cs.updated_at,
                    cs.remarks,
                    -- default signatory name by designation linkage (generic)
                    CONCAT(u.first_name,' ',u.last_name) AS generic_signatory_name,
                    -- program head name for applicant's department (if resolvable)
                    (
                        SELECT CONCAT(u2.first_name,' ',u2.last_name)
                        FROM staff sp
                        JOIN users u2 ON u2.user_id = sp.user_id
                        WHERE sp.staff_category = 'Program Head' AND sp.is_active = 1
                          AND (:dept_id IS NOT NULL AND sp.department_id = :dept_id)
                        LIMIT 1
                    ) AS ph_signatory_name
                FROM clearance_signatories cs 
                JOIN designations d ON d.designation_id=cs.designation_id 
                LEFT JOIN staff s ON s.designation_id=cs.designation_id AND s.is_active=1 
                LEFT JOIN users u ON u.user_id=s.user_id 
                WHERE cs.clearance_form_id=?";
    $sigStmt=$pdo->prepare($sqlSig);
    $bindDept = $deptId !== null ? $deptId : null;
    $sigStmt->bindValue(':dept_id', $bindDept, $bindDept===null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $sigStmt->execute([$form['clearance_form_id']]);
    $signatories=$sigStmt->fetchAll(PDO::FETCH_ASSOC);

    $overall=$form['status'];
    $total=count($signatories);
    $approved=0;$active=0;
    foreach($signatories as $s){$a=$s['action'];if($a!==null&&$a!==''){$active++;}if($a==='Approved'){$approved++;}}
    if($active===0)$overall='Unapplied';
    elseif($approved===$total)$overall='Complete';
    else $overall='In Progress';

    // Post-process to choose correct signatory display name
    foreach ($signatories as &$row) {
        if (strcasecmp($row['designation_name'], 'Program Head') === 0 && !empty($row['ph_signatory_name'])) {
            $row['signatory_name'] = $row['ph_signatory_name'];
        } else {
            $row['signatory_name'] = $row['generic_signatory_name'] ?? null;
        }
        unset($row['generic_signatory_name'], $row['ph_signatory_name']);
    }

    echo json_encode(['success'=>true,'overall_status'=>$overall,'total'=>$total,'approved'=>$approved,'signatories'=>$signatories,'department_id'=>$deptId]);
}catch(Exception $e){http_response_code(500);echo json_encode(['success'=>false,'message'=>$e->getMessage()]);}
