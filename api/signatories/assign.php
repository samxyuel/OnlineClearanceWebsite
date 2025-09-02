<?php
// -----------------------------------------------------------------------------
// Signatory Assignment Endpoint  (Phase 3B – step 1)
// Method: POST  – Assign a user (staff) as a signatory / designate role
// -----------------------------------------------------------------------------
// Expected JSON body:
// {
//   "user_id": 27,
//   "designation": "Program Head",     // e.g. Program Head, Cashier, Librarian
//   "department_id": 5,                 // optional – required for program heads
//   "staff_category": "special"        // regular | special (school admin / program head)
// }
// -----------------------------------------------------------------------------
// TODO – implement full database logic & permission checks
// -----------------------------------------------------------------------------

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // CORS pre-flight
    http_response_code(204);
    exit;
}

require_once '../../includes/config/database.php';
require_once '../../includes/classes/Auth.php';

// -----------------------------------------------------------------------------
// 1. Authentication check
// -----------------------------------------------------------------------------
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// (Optional) add a permission helper later, e.g. $auth->hasPermission('manage_signatories')

// -----------------------------------------------------------------------------
// 2. Only POST allowed
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// -----------------------------------------------------------------------------
// 3. Parse and validate JSON body
// -----------------------------------------------------------------------------
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON body']);
    exit;
}

$userId        = isset($input['user_id']) ? (int)$input['user_id'] : 0;
if (!$userId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => "Field 'user_id' is required"]);
    exit;
}
$designation   = isset($input['designation']) ? trim($input['designation']) : '';
$departmentId  = isset($input['department_id']) ? (int)$input['department_id'] : null;
$staffCategory = $input['staff_category'] ?? 'regular';
$transfer      = !empty($input['transfer']) && ($input['transfer'] === true || $input['transfer'] === 'true' || $input['transfer'] === 1 || $input['transfer'] === '1');

// -----------------------------------------------------------------------------
// 4. Business logic – create / update signatory assignment
// -----------------------------------------------------------------------------
try {
    $db         = Database::getInstance();
    $pdo        = $db->getConnection();

    // Guard: block signatory changes during active/deactivated periods
    try {
        $chk = $pdo->query("SELECT COUNT(*) FROM clearance_periods WHERE status IN ('active','deactivated')")->fetchColumn();
        if ((int)$chk > 0) {
            http_response_code(423);
            echo json_encode(['success'=>false,'message'=>'Signatory changes are locked while a clearance period is active or paused.']);
            exit;
        }
    } catch (Exception $e) { /* if table missing, skip guard */ }

    // 4.1  Determine designation_id
    $designationId = 0;
    if ($designation !== '') {
        $stmt = $pdo->prepare("SELECT designation_id FROM designations WHERE designation_name = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$designation]);
        $designationRow = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($designationRow) {
            $designationId = (int)$designationRow['designation_id'];
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid or inactive designation']);
            exit;
        }
    } else {
        // Infer from staff record
        $stmt = $pdo->prepare("SELECT s.designation_id, d.designation_name FROM staff s LEFT JOIN designations d ON d.designation_id=s.designation_id WHERE s.user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || empty($row['designation_id'])) {
            http_response_code(409);
            echo json_encode(['success'=>false,'message'=>'Staff has no designation; assign one first.']);
            exit;
        }
        $designationId = (int)$row['designation_id'];
        $designation = $row['designation_name'];
    }

    // 4.2  Validate user exists & is active
    $stmt = $pdo->prepare("SELECT user_id, status FROM users WHERE user_id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $userRow = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$userRow) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    if ($userRow['status'] !== 'active') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User is not active']);
        exit;
    }

    // Branch: Program Head vs Regular Staff scopes
    $isPH = (strcasecmp($designation, 'Program Head') === 0);

    if ($isPH) {
        if (!$departmentId) {
            http_response_code(400);
            echo json_encode(['success'=>false,'message'=>'department_id is required for Program Head']);
            exit;
        }
        // Resolve sector for department
        $secStmt = $pdo->prepare("SELECT sector_id FROM departments WHERE department_id = ? LIMIT 1");
        $secStmt->execute([$departmentId]);
        $sectorId = (int)$secStmt->fetchColumn();

        // Enforce uniqueness per department & designation
        $pdo->beginTransaction();
        if ($transfer) {
            $pdo->prepare("UPDATE signatory_assignments SET is_active=0, updated_at=NOW() WHERE department_id=? AND designation_id=? AND is_active=1")
                ->execute([$departmentId, $designationId]);
        } else {
            $chk = $pdo->prepare("SELECT COUNT(*) FROM signatory_assignments WHERE department_id=? AND designation_id=? AND is_active=1");
            $chk->execute([$departmentId, $designationId]);
            if ((int)$chk->fetchColumn() > 0) {
                http_response_code(409);
                echo json_encode(['success'=>false,'message'=>'Another active Program Head already exists for this department']);
                $pdo->rollBack();
                exit;
            }
        }
        // Upsert assignment for this user
        $sel = $pdo->prepare("SELECT assignment_id FROM signatory_assignments WHERE user_id=? AND designation_id=? AND department_id=? LIMIT 1");
        $sel->execute([$userId, $designationId, $departmentId]);
        $aid = $sel->fetchColumn();
        if ($aid) {
            $pdo->prepare("UPDATE signatory_assignments SET sector_id=?, clearance_type=NULL, is_active=1, updated_at=NOW() WHERE assignment_id=?")
                ->execute([$sectorId, (int)$aid]);
        } else {
            $pdo->prepare("INSERT INTO signatory_assignments (user_id, designation_id, clearance_type, department_id, sector_id, is_active) VALUES (?,?,?,?,?,1)")
                ->execute([$userId, $designationId, null, $departmentId, $sectorId]);
        }
        $pdo->commit();
        require_once '../../includes/functions/audit_functions.php';
        logActivity($auth->getUserId(), 'Signatory Assign (PH)', ['user_id'=>$userId,'department_id'=>$departmentId]);
        echo json_encode(['success'=>true,'message'=>'Program Head assignment saved']);
        exit;
    }

    // Regular Staff scopes: expect clearance_type
    $clearanceType = isset($input['clearance_type']) ? strtolower(trim($input['clearance_type'])) : '';
    if ($clearanceType !== 'student' && $clearanceType !== 'faculty') {
        http_response_code(400);
        echo json_encode(['success'=>false,'message'=>'clearance_type must be student or faculty']);
        exit;
    }
    // Upsert scope assignment
    $sel = $pdo->prepare("SELECT assignment_id FROM signatory_assignments WHERE user_id=? AND designation_id=? AND clearance_type=? LIMIT 1");
    $sel->execute([$userId, $designationId, $clearanceType]);
    $aid = $sel->fetchColumn();
    if ($aid) {
        $pdo->prepare("UPDATE signatory_assignments SET is_active=1, updated_at=NOW() WHERE assignment_id=?")
            ->execute([(int)$aid]);
    } else {
        $pdo->prepare("INSERT INTO signatory_assignments (user_id, designation_id, clearance_type, department_id, sector_id, is_active) VALUES (?,?,?,?,?,1)")
            ->execute([$userId, $designationId, $clearanceType, null, null]);
    }
    require_once '../../includes/functions/audit_functions.php';
    logActivity($auth->getUserId(), 'Signatory Assign (Scope)', ['user_id'=>$userId,'clearance_type'=>$clearanceType]);
    echo json_encode(['success'=>true,'message'=>'Staff scope assignment saved']);
    exit;

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
// -----------------------------------------------------------------------------
// End of file
// -----------------------------------------------------------------------------
