<?php
// -----------------------------------------------------------------------------
// Bulk Signatory Assignment Endpoint (Phase 3B – step 4)
// Method: POST – Assign or update multiple signatories in one request
// -----------------------------------------------------------------------------
// Expected JSON body example:
// {
//   "assignments": [
//     { "user_id": 27, "designation": "Program Head", "department_id": 5 },
//     { "user_id": 30, "designation": "Cashier" }
//   ]
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
if (!$input || empty($input['assignments']) || !is_array($input['assignments'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Field "assignments" must be a non-empty array']);
    exit;
}

try {
    $db         = Database::getInstance();
    $connection = $db->getConnection();
    $connection->beginTransaction();

    // Helper prepared statements (reuse inside loop)
    $designationStmt = $connection->prepare("SELECT designation_id FROM designations WHERE designation_name = ? AND is_active = 1 LIMIT 1");
    $userStmt        = $connection->prepare("SELECT user_id, status FROM users WHERE user_id = ? LIMIT 1");
    $duplicatePHStmt = $connection->prepare("SELECT employee_number FROM staff WHERE department_id = ? AND staff_category = 'Program Head' AND is_active = 1 AND user_id <> ? LIMIT 1");
    $existingStaffStmt = $connection->prepare("SELECT employee_number FROM staff WHERE user_id = ? LIMIT 1");
    $updateStmt = $connection->prepare("UPDATE staff SET designation_id = ?, staff_category = ?, department_id = ?, is_active = 1, updated_at = NOW() WHERE user_id = ?");
    $insertStmt = $connection->prepare("INSERT INTO staff (employee_number, user_id, designation_id, staff_category, department_id, is_active, created_at, updated_at) VALUES (?,?,?,?,?,1,NOW(),NOW())");

    $results = [];
    foreach ($input['assignments'] as $row) {
        $rowResult = [ 'user_id' => $row['user_id'] ?? null ];

        // Basic validation
        if (empty($row['user_id']) || empty($row['designation'])) {
            $rowResult['status']  = 'error';
            $rowResult['message'] = 'user_id and designation required';
            $results[] = $rowResult;
            continue;
        }
        $userId        = (int)$row['user_id'];
        $designation   = trim($row['designation']);
        $departmentId  = $row['department_id'] ?? null;
        $staffCategory = $row['staff_category'] ?? 'Regular Staff';

        // 1) Validate designation
        $designationStmt->execute([$designation]);
        $desRow = $designationStmt->fetch(PDO::FETCH_ASSOC);
        if (!$desRow) {
            $rowResult['status']  = 'error';
            $rowResult['message'] = 'invalid designation';
            $results[] = $rowResult;
            continue;
        }
        $designationId = (int)$desRow['designation_id'];

        // 2) Validate user
        $userStmt->execute([$userId]);
        $uRow = $userStmt->fetch(PDO::FETCH_ASSOC);
        if (!$uRow || $uRow['status'] !== 'active') {
            $rowResult['status']  = 'error';
            $rowResult['message'] = 'user not found or inactive';
            $results[] = $rowResult;
            continue;
        }

        // 3) Program head uniqueness check
        $scNorm = ucfirst(strtolower($staffCategory));
        if ($scNorm === 'Program head' || strcasecmp($designation,'Program Head') === 0) {
            if (!$departmentId) {
                $rowResult['status'] = 'error';
                $rowResult['message'] = 'department_id required for Program Head';
                $results[] = $rowResult;
                continue;
            }
            $duplicatePHStmt->execute([$departmentId, $userId]);
            if ($duplicatePHStmt->fetch()) {
                $rowResult['status'] = 'error';
                $rowResult['message']= 'another Program Head already active in department';
                $results[] = $rowResult;
                continue;
            }
        }

        // 4) Insert / update
        $existingStaffStmt->execute([$userId]);
        $existing = $existingStaffStmt->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
            $updateStmt->execute([$designationId, $scNorm, $departmentId, $userId]);
            $rowResult['status'] = 'updated';
            $rowResult['employee_id'] = $existing['employee_number'];
        } else {
            // new employee_number (temporary placeholder until LCA pattern assignment)
            $max = $connection->query("SELECT MAX(CAST(SUBSTRING(employee_number,4) AS UNSIGNED)) FROM staff")->fetchColumn();
            $newEmpId = 'EMP' . str_pad(((int)$max)+1,5,'0',STR_PAD_LEFT);
            $insertStmt->execute([$newEmpId, $userId, $designationId, $scNorm, $departmentId]);
            $rowResult['status'] = 'inserted';
            $rowResult['employee_id'] = $newEmpId;
        }
        $results[] = $rowResult;
    }

    $connection->commit();

    require_once '../../includes/functions/audit_functions.php';
    logActivity($auth->getUserId(), 'Bulk Signatory Assign', [ 'results'=>$results ]);

    echo json_encode([
        'success'   => true,
        'processed' => count($results),
        'results'   => $results
    ]);

} catch (Exception $e) {
    if ($connection && $connection->inTransaction()) {
        $connection->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
