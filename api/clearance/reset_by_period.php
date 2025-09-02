<?php
// -----------------------------------------------------------------------------
// Reset SPECIFIC Clearance Period by period_id (Admin only)
// POST /api/clearance/reset_by_period.php
// Body: { period_id: number }
// Safety:
//  // • Refuses if period not found
//  // • Refuses if period is active (use reset_period.php for current active)
// Behaviour:
//  // • Uses the period's academic_year_id and semester_id to reset
//  // • Sets clearance_signatories.action=NULL, remarks=NULL
//  // • Sets clearance_forms.status='Unapplied'
//  // • Writes an audit log
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
if(!$auth->isLoggedIn()){
    http_response_code(401); echo json_encode(['success'=>false,'message'=>'Authentication required']); exit;
}

// Only admins allowed
if($auth->getRoleName() !== 'Admin'){
    http_response_code(403); echo json_encode(['success'=>false,'message'=>'Admin permission required']); exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
$periodId = isset($payload['period_id']) ? (int)$payload['period_id'] : 0;
if(!$periodId){ http_response_code(400); echo json_encode(['success'=>false,'message'=>'period_id is required']); exit; }

try{
    $pdo = Database::getInstance()->getConnection();
    // Look up period
    $stmt = $pdo->prepare("SELECT academic_year_id, semester_id, is_active, status FROM clearance_periods WHERE period_id=? LIMIT 1");
    $stmt->execute([$periodId]);
    $period = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$period){ http_response_code(404); echo json_encode(['success'=>false,'message'=>'Period not found']); exit; }

    // Enforce: reset allowed ONLY for deactivated periods
    if($period['status'] === 'ended'){
        http_response_code(400); echo json_encode(['success'=>false,'message'=>'Cannot reset an ended period.']); exit;
    }
    if((int)$period['is_active'] === 1 || $period['status'] === 'active'){
        http_response_code(400); echo json_encode(['success'=>false,'message'=>'Cannot reset an active period. Deactivate it first.']); exit;
    }
    if($period['status'] !== 'deactivated'){
        http_response_code(400); echo json_encode(['success'=>false,'message'=>'Reset is allowed only for deactivated periods.']); exit;
    }

    $ayId = (int)$period['academic_year_id'];
    $semId = (int)$period['semester_id'];

    $pdo->beginTransaction();

    // Reset signatories joined via forms in that AY+semester
    $resetSig = $pdo->prepare("UPDATE clearance_signatories cs
        JOIN clearance_forms cf ON cf.clearance_form_id = cs.clearance_form_id
        SET cs.action=NULL, cs.remarks=NULL, cs.updated_at=NULL
        WHERE cf.academic_year_id=? AND cf.semester_id=?");
    $resetSig->execute([$ayId,$semId]);

    // Reset forms statuses
    $resetForms = $pdo->prepare("UPDATE clearance_forms SET status='Unapplied', updated_at=NOW() WHERE academic_year_id=? AND semester_id=?");
    $resetForms->execute([$ayId,$semId]);

    logActivity($auth->getUserId(),'reset_clearance_period_by_id',[
        'academic_year_id'=>$ayId,
        'semester_id'     =>$semId,
        'period_id'       =>$periodId
    ]);

    $pdo->commit();
    echo json_encode(['success'=>true,'reset_count'=>$resetForms->rowCount()]);

}catch(Exception $e){
    if($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
// -----------------------------------------------------------------------------


