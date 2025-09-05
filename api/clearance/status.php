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
        // Get active clearance period (same logic as user_periods.php)
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

        // Clearance form (if any)
        $formStmt = $pdo->prepare("SELECT clearance_form_id, status FROM clearance_forms WHERE user_id=? AND academic_year_id=? AND semester_id=? LIMIT 1");
        $formStmt->execute([$userId,$ayId,$semId]);
        $form = $formStmt->fetch(PDO::FETCH_ASSOC);
    }

    // NEW APPROACH: Show signatories even without clearance form
    if (!$form) {
        // Get user's role to determine clearance type
        // First try to get primary role, if not found, get any role
        $roleStmt = $pdo->prepare("SELECT r.role_name FROM users u JOIN user_roles ur ON u.user_id = ur.user_id JOIN roles r ON ur.role_id = r.role_id WHERE u.user_id = ? AND ur.is_primary = 1");
        $roleStmt->execute([$userId]);
        $role = $roleStmt->fetchColumn();
        
        // If no primary role found, get any role for this user
        if (!$role) {
            $roleStmt = $pdo->prepare("SELECT r.role_name FROM users u JOIN user_roles ur ON u.user_id = ur.user_id JOIN roles r ON ur.role_id = r.role_id WHERE u.user_id = ? LIMIT 1");
            $roleStmt->execute([$userId]);
            $role = $roleStmt->fetchColumn();
        }
        
        $clearanceType = ($role === 'Faculty') ? 'Faculty' : 'Student';
        
        // Get assigned signatories for this clearance type
        $signatories = getAssignedSignatories($pdo, $clearanceType, $userId);
        
        echo json_encode([
            'success' => true,
            'applied' => false,
            'clearance_form_id' => null,
            'overall_status' => 'Unapplied',
            'signatories' => $signatories,
            'period_status' => getPeriodStatus($pdo, $ayId, $semId)
        ]);
        exit;
    }

    // Fetch signatories from existing clearance form
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
        } elseif ($approvedCnt === $total) {
            $overall = 'Complete';
        } elseif ($activeCnt > 0) {
            // User has applied to one or more signatories
            $overall = 'In Progress';
        } else {
            $overall = 'Unapplied';
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

// Helper function to get assigned signatories for a clearance type
function getAssignedSignatories($pdo, $clearanceType, $userId = null) {
    // Get regular signatories assigned to this clearance type
    $sql = "SELECT 
                d.designation_id,
                d.designation_name,
                CONCAT(u.first_name,' ',u.last_name) AS signatory_name
            FROM signatory_assignments sa
            JOIN designations d ON d.designation_id = sa.designation_id
            LEFT JOIN staff s ON s.designation_id = d.designation_id AND s.is_active = 1
            LEFT JOIN users u ON u.user_id = s.user_id
            WHERE sa.clearance_type = ? 
            AND sa.is_active = 1 
            AND d.is_active = 1
            ORDER BY d.designation_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$clearanceType]);
    $signatories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if Program Head should be included based on scope settings
    $includeProgramHead = shouldIncludeProgramHead($pdo, $clearanceType);
    
    // If Program Head should be included and we have a user ID, find the appropriate Program Head
    if ($includeProgramHead && $userId) {
        $userDepartment = getUserDepartment($pdo, $userId, $clearanceType);
        if ($userDepartment) {
            $programHeadDesignationId = getProgramHeadDesignationId($pdo);
            if ($programHeadDesignationId) {
                // Find Program Head assigned to user's department
                $phSql = "SELECT 
                            d.designation_id,
                            d.designation_name,
                            CONCAT(u.first_name,' ',u.last_name) AS signatory_name
                          FROM signatory_assignments sa
                          JOIN designations d ON d.designation_id = sa.designation_id
                          JOIN staff s ON s.user_id = sa.user_id AND s.designation_id = sa.designation_id AND s.is_active = 1
                          JOIN users u ON u.user_id = s.user_id
                          WHERE sa.designation_id = ? 
                          AND sa.department_id = ? 
                          AND sa.is_active = 1
                          AND d.is_active = 1
                          LIMIT 1";
                
                $phStmt = $pdo->prepare($phSql);
                $phStmt->execute([$programHeadDesignationId, $userDepartment['department_id']]);
                $programHead = $phStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($programHead) {
                    $signatories[] = $programHead;
                }
            }
        }
    }
    
    // Add default values for signatories without clearance form
    foreach ($signatories as &$signatory) {
        $signatory['action'] = null;
        $signatory['updated_at'] = null;
        $signatory['remarks'] = null;
    }
    
    return $signatories;
}

// Helper function to get user's department based on clearance type
function getUserDepartment($pdo, $userId, $clearanceType) {
    if ($clearanceType === 'Faculty') {
        // For faculty, get their department from staff table
        $sql = "SELECT d.department_id, d.department_name, d.sector_id 
                FROM staff s 
                JOIN departments d ON d.department_id = s.department_id 
                WHERE s.user_id = ? AND s.is_active = 1 
                LIMIT 1";
    } else {
        // For students, get their department from students table
        $sql = "SELECT d.department_id, d.department_name, d.sector_id 
                FROM students s 
                JOIN departments d ON d.department_id = s.department_id 
                WHERE s.user_id = ? AND s.is_active = 1 
                LIMIT 1";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Helper function to check if Program Head should be included based on scope settings
function shouldIncludeProgramHead($pdo, $clearanceType) {
    $sql = "SELECT include_program_head FROM scope_settings WHERE clearance_type = ? LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$clearanceType]);
    $result = $stmt->fetchColumn();
    
    // If no setting exists, default to false
    return $result ? (bool)$result : false;
}

// Helper function to get Program Head designation ID
function getProgramHeadDesignationId($pdo) {
    $sql = "SELECT designation_id FROM designations WHERE designation_name = 'Program Head' AND is_active = 1 LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchColumn();
}

// Helper function to get period status
function getPeriodStatus($pdo, $ayId, $semId) {
    $stmt = $pdo->prepare("SELECT status, is_active FROM clearance_periods WHERE academic_year_id = ? AND semester_id = ? LIMIT 1");
    $stmt->execute([$ayId, $semId]);
    $period = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $period ? $period['status'] : 'inactive';
}
?>
