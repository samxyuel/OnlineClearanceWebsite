<?php
// -----------------------------------------------------------------------------
// Global Apply-for-Clearance Endpoint  (Phase 3C)
// Method: POST – End user applies to all signatories at once for the active period
// -----------------------------------------------------------------------------
// Behaviour:
//   • Finds active academic year & semester
//   • Ensures a clearance_forms row does NOT already exist for the user+period
//   • Creates clearance_form row with trigger-generated clearance_form_id
//   • (Optional stub) Future: pre-populate clearance_signatories rows
// -----------------------------------------------------------------------------

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once '../../includes/config/database.php';
require_once '../../includes/classes/Auth.php';
require_once '../../includes/functions/audit_functions.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Authentication required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Method not allowed']);
    exit;
}

$userId = $auth->getUserId();

try {
    $pdo = Database::getInstance()->getConnection();

    // Apply gate: require active period with status 'active'
    $cpStmt = $pdo->query("SELECT academic_year_id, semester_id, status FROM clearance_periods WHERE is_active=1 LIMIT 1");
    $cp = $cpStmt->fetch(PDO::FETCH_ASSOC);
    if(!$cp || $cp['status'] !== 'active'){
        http_response_code(400); echo json_encode(['success'=>false,'message'=>'No active clearance period for applications']); exit;
    }
    $ayId  = (int)$cp['academic_year_id'];
    $semId = (int)$cp['semester_id'];

    // User eligibility: status active and can_apply=1
    $uStmt = $pdo->prepare("SELECT status, COALESCE(can_apply,1) AS can_apply FROM users WHERE user_id=? LIMIT 1");
    $uStmt->execute([$userId]);
    $u = $uStmt->fetch(PDO::FETCH_ASSOC);
    if(!$u || strtolower($u['status']) !== 'active'){
        http_response_code(403); echo json_encode(['success'=>false,'message'=>'Account is inactive']); exit;
    }
    if((int)$u['can_apply'] !== 1){
        http_response_code(403); echo json_encode(['success'=>false,'message'=>'Applications are currently unavailable for your account']); exit;
    }

    // 2. Check if a form already exists
    $dupStmt = $pdo->prepare("SELECT clearance_form_id, status FROM clearance_forms WHERE user_id = ? AND academic_year_id = ? AND semester_id = ? LIMIT 1");
    $dupStmt->execute([$userId,$ayId,$semId]);
    $existing = $dupStmt->fetch(PDO::FETCH_ASSOC);

    if($existing && $existing['status']!=='Unapplied'){
        echo json_encode(['success'=>false,'message'=>'Clearance form already exists for this period']);
        exit;
    }

    $pdo->beginTransaction();

    if(!$existing){
        // 3. Insert new clearance_form
        $insert = $pdo->prepare("INSERT INTO clearance_forms (user_id, academic_year_id, semester_id, clearance_type, status, created_at, updated_at) VALUES (?,?,?,?, 'Unapplied', NOW(), NOW())");
        $role = $auth->getRoleName();
        $ctype = ($role === 'Faculty') ? 'Faculty' : 'Student';
        $insert->execute([$userId,$ayId,$semId,$ctype]);

        $formIdStmt = $pdo->prepare("SELECT clearance_form_id FROM clearance_forms WHERE user_id=? AND academic_year_id=? AND semester_id=? LIMIT 1");
        $formIdStmt->execute([$userId,$ayId,$semId]);
        $formId = $formIdStmt->fetchColumn();
    }else{
        $formId = $existing['clearance_form_id'];
    }

    // Determine clearance type (again if existing)
    if(!isset($ctype)){
        $role = $auth->getRoleName();
        $ctype = ($role === 'Faculty') ? 'Faculty' : 'Student';
    }

    // 4. Determine required signatories from actual assignments
    $reqStmt = $pdo->prepare("
        SELECT DISTINCT sa.designation_id 
        FROM signatory_assignments sa
        JOIN designations d ON d.designation_id = sa.designation_id
        WHERE sa.clearance_type = ? 
        AND sa.is_active = 1 
        AND d.is_active = 1
        ORDER BY sa.designation_id
    ");
    $reqStmt->execute([$ctype]);
    $designations = $reqStmt->fetchAll(PDO::FETCH_COLUMN);

    // Fallback: if no signatories assigned, check if Program Head should be included
    if(empty($designations)){
        // Check if Program Head is enabled in scope settings
        $scopeStmt = $pdo->prepare("SELECT include_program_head FROM scope_settings WHERE clearance_type = ? LIMIT 1");
        $scopeStmt->execute([$ctype]);
        $scopeSettings = $scopeStmt->fetch(PDO::FETCH_ASSOC);
        
        if($scopeSettings && $scopeSettings['include_program_head'] == 1) {
            try {
                $phId = $pdo->query("SELECT designation_id FROM designations WHERE designation_name='Program Head' AND is_active=1 LIMIT 1")->fetchColumn();
                if ($phId) {
                    $designations[] = (int)$phId;
                }
            } catch (Exception $e) { /* ignore missing designations table or record */ }
        }
        
        // If still empty, use minimal fallback
        if(empty($designations)){
            $designations = ($ctype==='Faculty') ? [1] : [1]; // Just Registrar as fallback
        }
    }

    // De-duplicate and ensure integers
    $designations = array_values(array_unique(array_map('intval', $designations)));

    // Remove any previous signatory rows (if admin reset kept rows but user re-applies)
    $pdo->prepare("DELETE FROM clearance_signatories WHERE clearance_form_id=?")->execute([$formId]);

    $insSig = $pdo->prepare("INSERT INTO clearance_signatories (clearance_form_id, designation_id, action, created_at, updated_at) VALUES (?,?, 'Pending', NOW(), NOW())");
    foreach($designations as $dId){
        $insSig->execute([$formId,$dId]);
    }

    // 5. Update form status to Applied (rows are pending, no approvals yet)
    $pdo->prepare("UPDATE clearance_forms SET status='Applied', updated_at=NOW() WHERE clearance_form_id=?")->execute([$formId]);

    logActivity($userId,'Global Apply', ['clearance_form_id'=>$formId,'academic_year_id'=>$ayId,'semester_id'=>$semId]);

    $pdo->commit();

    echo json_encode(['success'=>true,'message'=>'Applied to all signatories','clearance_form_id'=>$formId]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
// -----------------------------------------------------------------------------
