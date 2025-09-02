<?php
// -----------------------------------------------------------------------------
// GET current clearance status for logged-in user
// Returns list of signatories (designation_id, designation_name, action, updated_at, remarks)
// If user has no clearance form yet for active period -> { success:true, applied:false }
// -----------------------------------------------------------------------------
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit; }

require_once '../../includes/config/database.php';
require_once '../../includes/classes/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401); echo json_encode(['success'=>false,'message'=>'Authentication required']); exit; }

$userId = $auth->getUserId();

try {
    $pdo = Database::getInstance()->getConnection();

    // Check if specific form_id is requested
    $requestedFormId = $_GET['form_id'] ?? null;
    
    if ($requestedFormId) {
        // Fetch specific form by ID
        $formStmt = $pdo->prepare("SELECT clearance_form_id, status, academic_year_id, semester_id FROM clearance_forms WHERE clearance_form_id=? AND user_id=? LIMIT 1");
        $formStmt->execute([$requestedFormId, $userId]);
        $form = $formStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$form) {
            http_response_code(404);
            echo json_encode(['success'=>false,'message'=>'Clearance form not found']);
            exit;
        }
        
        $ayId = $form['academic_year_id'];
        $semId = $form['semester_id'];
    } else {
        // Active academic year & semester
        $ayId = $pdo->query("SELECT academic_year_id FROM academic_years WHERE is_active=1 LIMIT 1")->fetchColumn();
        $semIdStmt = $pdo->prepare("SELECT semester_id FROM semesters WHERE academic_year_id=? AND is_active=1 LIMIT 1");
        $semIdStmt->execute([$ayId]);
        $semId = $semIdStmt->fetchColumn();
        
        if (!$ayId || !$semId) {
            echo json_encode(['success'=>true,'applied'=>false,'message'=>'No active academic year/semester']);
            exit;
        }

        // Clearance form (if any)
        $formStmt = $pdo->prepare("SELECT clearance_form_id, status FROM clearance_forms WHERE user_id=? AND academic_year_id=? AND semester_id=? LIMIT 1");
        $formStmt->execute([$userId,$ayId,$semId]);
        $form = $formStmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$form) {
        echo json_encode(['success'=>true,'applied'=>false]);
        exit;
    }

    // Fetch signatories
    $sigStmt = $pdo->prepare("SELECT cs.designation_id, d.designation_name, cs.action, cs.updated_at, cs.remarks,
        CONCAT(u.first_name,' ',u.last_name) AS signatory_name
        FROM clearance_signatories cs
        JOIN designations d ON d.designation_id = cs.designation_id
        LEFT JOIN staff s ON s.designation_id = cs.designation_id AND s.is_active = 1
        LEFT JOIN users u ON u.user_id = s.user_id
        WHERE cs.clearance_form_id = ?");
    $sigStmt->execute([$form['clearance_form_id']]);
    $signatories = $sigStmt->fetchAll(PDO::FETCH_ASSOC);

    // Derive dynamic overall status (Unapplied | Applied | In Progress | Complete)
    $overall = $form['status'];
    if ($signatories) {
        $total        = count($signatories);
        $approvedCnt  = 0;
        $rejectedCnt  = 0;
        $pendingCnt   = 0; // action = 'Pending'
        $activeCnt    = 0; // actions that are NOT NULL / ''

        foreach ($signatories as $s) {
            $action = $s['action'];
            if ($action !== null && $action !== '') { $activeCnt++; }
            if ($action === 'Approved') { $approvedCnt++; }
            if ($action === 'Rejected') { $rejectedCnt++; }
            if ($action === 'Pending')  { $pendingCnt++; }
        }

        if ($activeCnt === 0) {
            $overall = 'Unapplied';
        } elseif ($approvedCnt === 0 && $rejectedCnt === 0 && $pendingCnt > 0) {
            // User has applied (rows exist / pending) but no approvals/rejections yet
            $overall = 'Applied';
        } elseif ($approvedCnt === $total) {
            $overall = 'Complete';
        } else {
            $overall = 'In Progress';
        }

        // Persist change if status differs
        if ($overall !== $form['status']) {
            $upd = $pdo->prepare("UPDATE clearance_forms SET status=?, updated_at=NOW() WHERE clearance_form_id=?");
            $upd->execute([$overall, $form['clearance_form_id']]);
        }
    }

    // Determine if user has taken any action yet (used by dashboards)
    $hasApplied = false;
    foreach($signatories as $s){
        if($s['action'] !== null && $s['action'] !== ''){ $hasApplied = true; break; }
    }

    echo json_encode([
        'success'            => true,
        'applied'            => $hasApplied,
        'clearance_form_id'  => $form['clearance_form_id'],
        'overall_status'     => $overall,
        'signatories'        => $signatories
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
?>
