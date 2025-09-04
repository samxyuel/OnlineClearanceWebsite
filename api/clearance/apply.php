 <?php
// -----------------------------------------------------------------------------
// Per-Signatory Apply / Re-apply Endpoint  (Phase 3C)
// Method: POST – User applies to a specific signatory for current clearance form
// -----------------------------------------------------------------------------
// Expected JSON body: one of the following
//   { "designation_id": 1 }
//   { "designation_code": "REGISTRAR" | "CASHIER" | "LIBRARIAN" | "PROGRAM_HEAD" }
//   { "designation_name": "Registrar" | "Cashier" | "Librarian" | "Program Head" }
// Behaviour:
//   • Ensures clearance_form exists for user+active period (creates if missing)
//   • Inserts or resets clearance_signatories row to Pending
// -----------------------------------------------------------------------------

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once '../../includes/config/database.php';
require_once '../../includes/classes/Auth.php';
require_once '../../includes/functions/audit_functions.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401); echo json_encode(['success'=>false,'message'=>'Authentication required']); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit; }

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || (!isset($input['designation_id']) && !isset($input['designation_code']) && !isset($input['designation_name']))) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'designation_id or designation_code or designation_name is required']);
    exit;
}

$pdo = null;
$designationId = null;
try {
    $pdo = Database::getInstance()->getConnection();

    if (isset($input['designation_id'])) {
        $designationId = (int)$input['designation_id'];
    } else {
        // Resolve via code or name
        $targetName = null;
        if (isset($input['designation_code'])) {
            $code = strtoupper(trim($input['designation_code']));
            $codeNameMap = [
                'REGISTRAR'     => 'Registrar',
                'CASHIER'       => 'Cashier',
                'LIBRARIAN'     => 'Librarian',
                'PROGRAM_HEAD'  => 'Program Head'
            ];
            if (!isset($codeNameMap[$code])) {
                http_response_code(400);
                echo json_encode(['success'=>false,'message'=>'Invalid designation_code']);
                exit;
            }
            $targetName = $codeNameMap[$code];
        } else if (isset($input['designation_name'])) {
            $targetName = trim($input['designation_name']);
        }

        if ($targetName) {
            $stmt = $pdo->prepare("SELECT designation_id FROM designations WHERE is_active=1 AND LOWER(designation_name)=LOWER(?) LIMIT 1");
            $stmt->execute([$targetName]);
            $designationId = (int)$stmt->fetchColumn();
            if (!$designationId) {
                http_response_code(400);
                echo json_encode(['success'=>false,'message'=>'Unknown designation_name']);
                exit;
            }
        }
    }
} catch (Exception $e) {
    http_response_code(500); echo json_encode(['success'=>false,'message'=>'Server error']); exit;
}
$userId       = $auth->getUserId();

try {
    if ($pdo === null) { $pdo = Database::getInstance()->getConnection(); }

    // Apply gate: active clearance period + user eligibility
    // 1) Ensure an active clearance period with status 'active' exists and derive AY+semester
    $cpStmt = $pdo->query("SELECT academic_year_id, semester_id, status FROM clearance_periods WHERE is_active=1 LIMIT 1");
    $cp = $cpStmt->fetch(PDO::FETCH_ASSOC);
    if(!$cp || $cp['status'] !== 'active'){
        http_response_code(400); echo json_encode(['success'=>false,'message'=>'No active clearance period for applications']); exit;
    }

    // 2) Ensure user is eligible (status active AND can_apply=1)
    $uStmt = $pdo->prepare("SELECT status, COALESCE(can_apply,1) AS can_apply FROM users WHERE user_id=? LIMIT 1");
    $uStmt->execute([$userId]);
    $u = $uStmt->fetch(PDO::FETCH_ASSOC);
    if(!$u || strtolower($u['status']) !== 'active'){
        http_response_code(403); echo json_encode(['success'=>false,'message'=>'Account is inactive']); exit;
    }
    if((int)$u['can_apply'] !== 1){
        http_response_code(403); echo json_encode(['success'=>false,'message'=>'Applications are currently unavailable for your account']); exit;
    }

    // Validate designation exists (final guard)
    $checkDesig = $pdo->prepare("SELECT designation_id FROM designations WHERE designation_id = ? AND is_active = 1 LIMIT 1");
    $checkDesig->execute([$designationId]);
    if (!$checkDesig->fetchColumn()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid designation']);
        exit;
    }

    // Use AY & semester from the active clearance period
    $ayId  = (int)$cp['academic_year_id'];
    $semId = (int)$cp['semester_id'];

    // get or create clearance_form
    $formStmt=$pdo->prepare("SELECT clearance_form_id FROM clearance_forms WHERE user_id=? AND academic_year_id=? AND semester_id=? LIMIT 1");
    $formStmt->execute([$userId,$ayId,$semId]);
    $formId=$formStmt->fetchColumn();
    $isNewForm = false;
    
    if(!$formId){
        $isNewForm = true;
        $ctype = ($auth->getRoleName()==='Faculty') ? 'Faculty' : 'Student';
        $pdo->prepare("INSERT INTO clearance_forms (user_id, academic_year_id, semester_id, clearance_type, status, created_at, updated_at) VALUES (?,?,?,?, 'Unapplied', NOW(), NOW())")
            ->execute([$userId,$ayId,$semId,$ctype]);
        // Re-query to fetch the trigger-generated VARCHAR id
        $formStmt->execute([$userId,$ayId,$semId]);
        $formId = $formStmt->fetchColumn();
        if(!$formId) throw new Exception('Failed to create clearance form');
        
        // NEW: Create all signatory entries for new form
        createAllSignatoryEntries($pdo, $formId, $ctype);
    }

    // Upsert signatory row
    $sigStmt=$pdo->prepare("SELECT signatory_id FROM clearance_signatories WHERE clearance_form_id=? AND designation_id=? LIMIT 1");
    $sigStmt->execute([$formId,$designationId]);
    if($sigStmt->fetch()){
        $pdo->prepare("UPDATE clearance_signatories SET action='Pending', remarks=NULL, rejection_reason_id=NULL, additional_remarks=NULL, updated_at=NOW() WHERE clearance_form_id=? AND designation_id=?")->execute([$formId,$designationId]);
    }else{
        $pdo->prepare("INSERT INTO clearance_signatories (clearance_form_id,designation_id,action,created_at,updated_at) VALUES (?,?, 'Pending', NOW(), NOW())")->execute([$formId,$designationId]);
    }

    // If form status is still Unapplied, bump it to Applied
    $pdo->prepare("UPDATE clearance_forms SET status='Applied', updated_at=NOW() WHERE clearance_form_id=? AND status='Unapplied'")->execute([$formId]);

    logActivity($userId,'Signatory Apply', ['form_id'=>$formId,'designation_id'=>$designationId]);
    echo json_encode(['success'=>true,'message'=>'Applied to signatory','clearance_form_id'=>$formId]);

}catch(Exception $e){ http_response_code(500); echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }

// Helper function to create all signatory entries for a new clearance form
function createAllSignatoryEntries($pdo, $formId, $clearanceType) {
    // Get all assigned signatories for this clearance type
    $sql = "SELECT DISTINCT sa.designation_id 
            FROM signatory_assignments sa
            JOIN designations d ON d.designation_id = sa.designation_id
            WHERE sa.clearance_type = ? 
            AND sa.is_active = 1 
            AND d.is_active = 1
            ORDER BY sa.designation_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$clearanceType]);
    $designations = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Create signatory entries for all assigned designations
    $insertStmt = $pdo->prepare("
        INSERT INTO clearance_signatories (clearance_form_id, designation_id, action, created_at, updated_at) 
        VALUES (?, ?, 'Unapplied', NOW(), NOW())
    ");
    
    foreach ($designations as $designationId) {
        $insertStmt->execute([$formId, $designationId]);
    }
}
// -----------------------------------------------------------------------------
