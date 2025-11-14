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
$reasonId        = isset($input['reason_id']) ? (int)$input['reason_id'] : null;
$additionalRemarks = isset($input['additional_remarks']) ? trim($input['additional_remarks']) : null; // Assuming frontend might send this separately
$designationId   = isset($input['designation_id']) ? (int)$input['designation_id'] : null;
$designationName = isset($input['designation_name']) ? trim($input['designation_name']) : null;
$actingUserId    = $auth->getUserId(); // Get the currently logged-in staff user

if ($action !== 'Approved' && $action !== 'Rejected') {
    http_response_code(400); echo json_encode(['success'=>false,'message'=>'Invalid action']); exit;
}

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Get the acting user's designation_id from the staff table based on their session user_id
    // If a designation_name is passed (like 'Program Head'), we might not need to look up the staff's single designation.
    if (!$designationId && !$designationName) {
        $staffStmt = $pdo->prepare("SELECT designation_id FROM staff WHERE user_id = ? AND is_active = 1");
        $staffStmt->execute([$actingUserId]);
        $designationId = $staffStmt->fetchColumn();
    }
    
    if (!$designationId && !$designationName) {
        http_response_code(400);
        echo json_encode(['success'=>false,'message'=>'Acting user has no valid designation or no designation was specified in the request.']);
        exit;
    }

    if ($designationName && !$designationId) {
        $desigIdStmt = $pdo->prepare("SELECT designation_id FROM designations WHERE designation_name = ?");
        $desigIdStmt->execute([$designationName]);
        $designationId = $desigIdStmt->fetchColumn();
    }

    // Active period
    $cp = $pdo->query("SELECT academic_year_id, semester_id, status FROM clearance_periods WHERE status = 'Ongoing' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$cp) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'No active clearance period']); exit; }
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
    $isProgramHeadRequest = (isset($designationName) && strcasecmp($designationName, 'Program Head') === 0);
    if (!$isProgramHeadRequest && $designationId) {
        $name = $pdo->prepare("SELECT designation_name FROM designations WHERE designation_id=? LIMIT 1");
        $name->execute([$designationId]);
        $dname = (string)$name->fetchColumn();
        $isProgramHeadRequest = (strcasecmp($dname, 'Program Head') === 0);
    }

    if ($isProgramHeadRequest) {
        // For Program Head, first check if they are included in the sector's settings.
        $settingsStmt = $pdo->prepare("SELECT include_program_head FROM sector_clearance_settings WHERE clearance_type = ? LIMIT 1");
        $settingsStmt->execute([$formData['clearance_type']]);
        $includeProgramHead = $settingsStmt->fetchColumn();

        if (!$includeProgramHead) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Program Head is not configured as a signatory for ' . htmlspecialchars($formData['clearance_type']) . ' clearances.']);
            exit;
        }

        // If included, verify department scope, UNLESS it's for Senior High School.
        if ($formData['clearance_type'] !== 'Senior High School') {
        // Get the applicant's department ID
        $applicantDeptId = null;
        if ($formData['clearance_type'] === 'Faculty') {
            $deptStmt = $pdo->prepare("SELECT department_id FROM faculty WHERE user_id = ? LIMIT 1");
            $deptStmt->execute([$applicantId]);
            $applicantDeptId = $deptStmt->fetchColumn();
        } else { // Assumes 'College'
            $deptStmt = $pdo->prepare("SELECT department_id FROM students WHERE user_id = ? LIMIT 1");
            $deptStmt->execute([$applicantId]);
            $applicantDeptId = $deptStmt->fetchColumn();
        }

        if (!$applicantDeptId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Applicant department not set']);
            exit;
        }

        // Ensure acting user is PH of that department
        $phCheckStmt = $pdo->prepare("SELECT 1 FROM staff WHERE user_id=? AND designation_id=8 AND is_active=1 AND department_id=? LIMIT 1");
        $phCheckStmt->execute([$actingUserId, (int)$applicantDeptId]);
        if (!$phCheckStmt->fetchColumn()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Only the Program Head of the applicant\'s department can perform this action']);
            exit;
        }
        }
    } else {
        // For other designations, perform a two-step check:
        // 1. Verify the acting user actually holds the designation they claim to be.
        // This now checks both the primary designation in `staff` and additional assignments in `user_designation_assignments`.
        $userHasDesigStmt = $pdo->prepare("
            SELECT 1 FROM (
                (SELECT designation_id FROM staff WHERE user_id = :user_id_1 AND is_active = 1 AND designation_id IS NOT NULL)
                UNION
                (SELECT designation_id FROM user_designation_assignments WHERE user_id = :user_id_2 AND is_active = 1)
            ) AS user_designations
            WHERE designation_id = :designation_id
            LIMIT 1
        ");
        $userHasDesigStmt->execute([':user_id_1' => $actingUserId, ':user_id_2' => $actingUserId, ':designation_id' => $designationId]);

        if (!$userHasDesigStmt->fetchColumn()) {
            $actingDesignationName = $designationName ?? "ID " . $designationId;
            http_response_code(403); echo json_encode(['success'=>false,'message'=>"Not Allowed to sign as " . htmlspecialchars($actingDesignationName)]); exit;
        }

        // 2. Verify that this designation is assigned to sign for the applicant's clearance type.
        // This allows any user with the correct designation to sign.
        $assignmentCheckStmt = $pdo->prepare("
            SELECT COUNT(*) FROM sector_signatory_assignments 
            WHERE designation_id = ? AND clearance_type = ? AND is_active = 1
        ");
        $assignmentCheckStmt->execute([$designationId, $formData['clearance_type']]);
        if ($assignmentCheckStmt->fetchColumn() == 0) {
            http_response_code(403); 
            echo json_encode(['success' => false, 'message' => 'The ' . htmlspecialchars($designationName) . ' designation is not assigned to sign for ' . htmlspecialchars($formData['clearance_type']) . ' clearances.']); 
            exit;
        }
    }

    // Required First/Last Signatory Validation
    $scopeSettings = $pdo->prepare("SELECT required_first_enabled, required_first_designation_id, required_last_enabled, required_last_designation_id FROM sector_clearance_settings WHERE clearance_type = ? LIMIT 1");
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
        if ($action === 'Approved') {
            // On approval, set the user ID and the signing date, clear rejection info.
            $upd = $pdo->prepare("UPDATE clearance_signatories SET action=?, remarks=?, reason_id=NULL, additional_remarks=NULL, updated_at=NOW(), actual_user_id=?, date_signed=NOW() WHERE clearance_form_id=? AND designation_id=?");
            $upd->execute([$action, $remarks, $actingUserId, $formId, $designationId]); // Use $remarks for general remarks
        } else {
            // On rejection, set rejection reason and additional remarks, clear actual_user_id and date_signed.
            // The 'remarks' from frontend is treated as 'additional_remarks' for rejection.
            $upd = $pdo->prepare("UPDATE clearance_signatories SET action=?, remarks=NULL, reason_id=?, additional_remarks=?, updated_at=NOW(), actual_user_id=NULL, date_signed=NULL WHERE clearance_form_id=? AND designation_id=?");
            $upd->execute([$action, $reasonId, $remarks, $formId, $designationId]);
        }
    } else {
        // Corrected INSERT logic
        $userIdToInsert = ($action === 'Approved') ? $actingUserId : null;
        $dateToInsert = ($action === 'Approved') ? date('Y-m-d H:i:s') : null;
        $generalRemarks = ($action === 'Approved') ? $remarks : null;
        $rejectionRemarks = ($action === 'Rejected') ? $remarks : null;

        $ins = $pdo->prepare("INSERT INTO clearance_signatories (clearance_form_id, designation_id, action, remarks, reason_id, additional_remarks, created_at, updated_at, actual_user_id, date_signed) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW(), ?, ?)");
        $ins->execute([$formId, $designationId, $action, $generalRemarks, $reasonId, $rejectionRemarks, $userIdToInsert, $dateToInsert]);
    }

    // Optionally lift overall status to In Progress or Complete
    // Count states
    $st = $pdo->prepare("SELECT action FROM clearance_signatories WHERE clearance_form_id=?");
    $st->execute([$formId]);
    $rows = $st->fetchAll(PDO::FETCH_COLUMN, 0);
    $total = count($rows);
    $approved = 0; $active = 0;
    foreach ($rows as $a) { if ($a!==null && $a!=='') $active++; if ($a==='Approved') $approved++; }
    $overallProgress = ($active === 0) ? 'unapplied' : (($approved === $total && $total > 0) ? 'complete' : 'in-progress');
    
    // Conditionally set completed_at timestamp
    if ($overallProgress === 'complete') {
        $pdo->prepare("UPDATE clearance_forms SET clearance_form_progress='complete', completed_at=NOW(), updated_at=NOW() WHERE clearance_form_id=?")->execute([$formId]);
    } else {
        $pdo->prepare("UPDATE clearance_forms SET clearance_form_progress=?, updated_at=NOW() WHERE clearance_form_id=?")->execute([$overallProgress, $formId]);
    }

    logActivity($actingUserId, 'Signatory Action', ['target_user_id' => $applicantId, 'designation_id' => $designationId, 'action' => $action]);
    echo json_encode(['success' => true, 'message' => 'Action recorded', 'overall_status' => $overallProgress]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
