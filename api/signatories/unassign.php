<?php
// -----------------------------------------------------------------------------
// Signatory Un-assign Endpoint (Phase 3B – step 2)
// Method: POST – Remove signatory role from a staff user
// -----------------------------------------------------------------------------
// Expected JSON body:
// {
//   "signatory_id": 123        // primary key of signatory record OR
//   "user_id": 27,             // and optional filters to identify record
//   "designation": "Program Head",
//   "department_id": 5
// }
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

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON body']);
    exit;
}

// -----------------------------------------------------------------------------
// 4. Business logic – deactivate staff / signatory record
// -----------------------------------------------------------------------------

$signatoryId  = $input['signatory_id'] ?? null;
$userId       = $input['user_id']       ?? null;
$designation  = $input['designation']  ?? null;
$departmentId = $input['department_id'] ?? null;

if (!$signatoryId && !$userId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Provide signatory_id or user_id']);
    exit;
}

try {
    $pdo = Database::getInstance()->getConnection();

    // Guard: block signatory changes during active/deactivated periods
    try {
        $chk = $pdo->query("SELECT COUNT(*) FROM clearance_periods WHERE status IN ('active','deactivated')")->fetchColumn();
        if ((int)$chk > 0) {
            http_response_code(423);
            echo json_encode(['success'=>false,'message'=>'Signatory changes are locked while a clearance period is active or paused.']);
            exit;
        }
    } catch (Exception $e) { /* skip if table missing */ }

    // If designation provided, lookup designation_id
    $designationId = null;
    if ($designation) {
        $stmt = $pdo->prepare("SELECT designation_id FROM designations WHERE designation_name = ? LIMIT 1");
        $stmt->execute([$designation]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid designation']);
            exit;
        }
        $designationId = (int)$row['designation_id'];
    }

    // Build query to find signatory assignment
    $where = [];
    $params = [];
    if ($userId) {
        $where[]  = 'user_id = ?';
        $params[] = $userId;
    }
    if ($designationId) {
        $where[]  = 'designation_id = ?';
        $params[] = $designationId;
    }
    
    // Add clearance_type filter if provided
    if (isset($input['clearance_type'])) {
        $where[]  = 'clearance_type = ?';
        $params[] = $input['clearance_type'];
    }

    if (empty($where)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Insufficient filters to locate signatory assignment']);
        exit;
    }

    // Look for signatory assignment in signatory_assignments table
    $query = 'SELECT assignment_id, user_id, designation_id, clearance_type, is_active FROM signatory_assignments WHERE ' . implode(' AND ', $where) . ' LIMIT 1';
    $stmt  = $pdo->prepare($query);
    $stmt->execute($params);
    $assignmentRow = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$assignmentRow) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Signatory assignment not found']);
        exit;
    }

    if ((int)$assignmentRow['is_active'] === 0) {
        echo json_encode(['success' => true, 'message' => 'Signatory assignment already inactive']);
        exit;
    }

    // Deactivate signatory assignment
    $update = 'UPDATE signatory_assignments SET is_active = 0, updated_at = NOW() WHERE assignment_id = ?';
    $pdo->prepare($update)->execute([$assignmentRow['assignment_id']]);

    echo json_encode([
        'success'     => true,
        'message'     => 'Signatory assignment removed successfully',
        'assignment_id' => $assignmentRow['assignment_id'],
        'user_id' => $assignmentRow['user_id'],
        'designation_id' => $assignmentRow['designation_id'],
        'clearance_type' => $assignmentRow['clearance_type']
    ]);

    require_once '../../includes/functions/audit_functions.php';
    logActivity($auth->getUserId(), 'Signatory Assignment Remove', [
        'target_user_id'=>$userId,
        'assignment_id'=>$assignmentRow['assignment_id'],
        'designation'=>$designation,
        'clearance_type'=>$assignmentRow['clearance_type']
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
// -----------------------------------------------------------------------------
// End of file
// -----------------------------------------------------------------------------
