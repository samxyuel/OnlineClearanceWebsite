<?php
// -----------------------------------------------------------------------------
// Signatory Action Endpoint (department-aware PH routing)
// Method: POST
// Body: {
//   "applicant_user_id": 123,                 // required - target applicant
//   "designation_id": 9,                      // or designation_name: "Program Head"
//   "designation_name": "Program Head",      // optional if id provided
//   "action": "Approved" | "Rejected",       // required
//   "remarks": "optional text"               // optional
// }
// Behavior:
//   - Validates active clearance period and ensures applicant's form exists
//   - Department-aware check: if designation is Program Head, the acting user must
//     be the PH for the applicant's department
//   - Updates clearance_signatories row (creates Pending row if missing), sets action
//   - Emits audit log
// -----------------------------------------------------------------------------

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit; }

require_once '../../includes/config/database.php';
require_once '../../includes/classes/Auth.php';
require_once '../../includes/functions/audit_functions.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Authentication required']); exit; }

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['applicant_user_id']) || empty($input['action'])) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'applicant_user_id and action are required']);
    exit;
}


$applicantId     = (int)$input['applicant_user_id'];
$action          = trim($input['action']);
$remarks         = isset($input['remarks']) ? trim($input['remarks']) : null;
$designationId   = isset($input['designation_id']) ? (int)$input['designation_id'] : null;
$designationName = isset($input['designation_name']) ? trim($input['designation_name']) : null;

if ($action !== 'Approved' && $action !== 'Rejected') {
    http_response_code(400); echo json_encode(['success'=>false,'message'=>'Invalid action']); exit;
}

try {
    $pdo = Database::getInstance()->getConnection();

    // Resolve designation_id if only name provided
    if (!$designationId && $designationName) {
        $stmt = $pdo->prepare("SELECT designation_id FROM designations WHERE designation_name = ? AND is_active=1 LIMIT 1");
        $stmt->execute([$designationName]);
        $designationId = (int)$stmt->fetchColumn();
    }
    if (!$designationId) { 
        http_response_code(400); 
        echo json_encode(['success'=>false,'message'=>'designation_id or designation_name required']); 
        exit; 
    }

    // Active period
    $cp = $pdo->query("SELECT academic_year_id, semester_id, status FROM clearance_periods WHERE is_active=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$cp || $cp['status'] !== 'active') { http_response_code(400); echo json_encode(['success'=>false,'message'=>'No active clearance period']); exit; }
    $ayId  = (int)$cp['academic_year_id'];
    $semId = (int)$cp['semester_id'];

    // Applicant form
    $formStmt = $pdo->prepare("SELECT clearance_form_id, clearance_type FROM clearance_forms WHERE user_id=? AND academic_year_id=? AND semester_id=? LIMIT 1");
    $formStmt->execute([$applicantId,$ayId,$semId]);
    $formData = $formStmt->fetch(PDO::FETCH_ASSOC);
    if (!$formData) { http_response_code(404); echo json_encode(['success'=>false,'message'=>'Applicant has no form for this period']); exit; }
    $formId = $formData['clearance_form_id'];
    $clearanceType = strtolower($formData['clearance_type']); // 'student' or 'faculty'

    // Department-aware routing for Program Head
    $actingUserId = $auth->getUserId();
    $isProgramHead = false;
    try {
        $name = $pdo->prepare("SELECT designation_name FROM designations WHERE designation_id=? LIMIT 1");
        $name->execute([$designationId]);
        $dname = (string)$name->fetchColumn();
        $isProgramHead = (strcasecmp($dname,'Program Head')===0);
    } catch (Exception $e) { /* ignore */ }

    if ($isProgramHead) {
        // Applicant department
        $deptId = null;
        $q1 = $pdo->prepare("SELECT department_id FROM faculty WHERE user_id=? LIMIT 1");
        $q1->execute([$applicantId]);
        $deptId = $q1->fetchColumn();
        if ($deptId === false || $deptId === null) {
            $q2 = $pdo->prepare("SELECT department_id FROM students WHERE user_id=? LIMIT 1");
            $q2->execute([$applicantId]);
            $deptId = $q2->fetchColumn();
        }
        if (!$deptId) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Applicant department not set']); exit; }

        // Ensure acting user is PH of that department
        $chk = $pdo->prepare("SELECT 1 FROM staff WHERE user_id=? AND staff_category='Program Head' AND is_active=1 AND department_id=? LIMIT 1");
        $chk->execute([$actingUserId,(int)$deptId]);
        if (!$chk->fetchColumn()) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Only the Program Head of the applicant\'s department can perform this action']); exit; }
    } else {
        // For other designations, ensure the acting user matches a staff with that designation (active)
        $chk = $pdo->prepare("SELECT 1 FROM staff WHERE user_id=? AND designation_id=? AND is_active=1 LIMIT 1");
        $chk->execute([$actingUserId,$designationId]);
        if (!$chk->fetchColumn()) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'You are not the assigned signatory for this designation']); exit; }
    }

    // Required First/Last Signatory Validation
    $scopeSettings = $pdo->prepare("SELECT required_first_enabled, required_first_designation_id, required_last_enabled, required_last_designation_id FROM scope_settings WHERE clearance_type = ? LIMIT 1");
    $scopeSettings->execute([$clearanceType]);
    $scope = $scopeSettings->fetch(PDO::FETCH_ASSOC);
    
    if ($scope) {
        // Check Required First validation
        if ($scope['required_first_enabled'] && $scope['required_first_designation_id']) {
            // Only check if the current signatory is NOT the required first signatory
            if ($designationId != $scope['required_first_designation_id']) {
                // Check if the required first signatory has already approved
                $firstCheck = $pdo->prepare("SELECT action FROM clearance_signatories WHERE clearance_form_id = ? AND designation_id = ? LIMIT 1");
                $firstCheck->execute([$formId, $scope['required_first_designation_id']]);
                $firstAction = $firstCheck->fetchColumn();
                
                if (!$firstAction || $firstAction !== 'Approved') {
                    // Get the designation name for error message
                    $firstDesignation = $pdo->prepare("SELECT designation_name FROM designations WHERE designation_id = ? LIMIT 1");
                    $firstDesignation->execute([$scope['required_first_designation_id']]);
                    $firstDesignationName = $firstDesignation->fetchColumn();
                    
                    http_response_code(400);
                    echo json_encode([
                        'success' => false, 
                        'message' => "Cannot approve: Required first signatory ({$firstDesignationName}) must approve before other signatories can act.",
                        'required_first' => $firstDesignationName
                    ]);
                    exit;
                }
            }
        }
        
        // Check Required Last validation
        if ($scope['required_last_enabled'] && $scope['required_last_designation_id'] && $designationId == $scope['required_last_designation_id']) {
            // Check if all other signatories have approved (except the required last)
            $otherSignatories = $pdo->prepare("
                SELECT cs.designation_id, cs.action, d.designation_name 
                FROM clearance_signatories cs 
                JOIN designations d ON d.designation_id = cs.designation_id 
                WHERE cs.clearance_form_id = ? AND cs.designation_id != ?
            ");
            $otherSignatories->execute([$formId, $designationId]);
            $otherActions = $otherSignatories->fetchAll(PDO::FETCH_ASSOC);
            
            $pendingSignatories = [];
            foreach ($otherActions as $other) {
                if (!$other['action'] || $other['action'] !== 'Approved') {
                    $pendingSignatories[] = $other['designation_name'];
                }
            }
            
            if (!empty($pendingSignatories)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'message' => "Cannot approve: All other signatories must approve before the required last signatory can act. Pending: " . implode(', ', $pendingSignatories),
                    'pending_signatories' => $pendingSignatories
                ]);
                exit;
            }
        }
    }

    // Upsert signatory row and set action
    $get = $pdo->prepare("SELECT 1 FROM clearance_signatories WHERE clearance_form_id=? AND designation_id=? LIMIT 1");
    $get->execute([$formId,$designationId]);
    if ($get->fetch()) {
        $upd = $pdo->prepare("UPDATE clearance_signatories SET action=?, remarks=?, updated_at=NOW() WHERE clearance_form_id=? AND designation_id=?");
        $upd->execute([$action,$remarks,$formId,$designationId]);
    } else {
        $ins = $pdo->prepare("INSERT INTO clearance_signatories (clearance_form_id,designation_id,action,remarks,created_at,updated_at) VALUES (?,?,?,?,NOW(),NOW())");
        $ins->execute([$formId,$designationId,$action,$remarks]);
    }

    // Optionally lift overall status to In Progress or Complete
    // Count states
    $st = $pdo->prepare("SELECT action FROM clearance_signatories WHERE clearance_form_id=?");
    $st->execute([$formId]);
    $rows = $st->fetchAll(PDO::FETCH_COLUMN,0);
    $total = count($rows);
    $approved = 0; $active = 0;
    foreach ($rows as $a) { if ($a!==null && $a!=='') $active++; if ($a==='Approved') $approved++; }
    $overall = ($active===0) ? 'Unapplied' : (($approved===$total) ? 'Complete' : 'In Progress');
    $pdo->prepare("UPDATE clearance_forms SET status=?, updated_at=NOW() WHERE clearance_form_id=?")->execute([$overall,$formId]);

    logActivity($actingUserId,'Signatory Action',[ 'target_user_id'=>$applicantId, 'designation_id'=>$designationId, 'action'=>$action ]);
    echo json_encode(['success'=>true,'message'=>'Action recorded','overall_status'=>$overall]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}


