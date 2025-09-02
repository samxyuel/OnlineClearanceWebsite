<?php
// -----------------------------------------------------------------------------
// Delete Staff (with Program Head / School Administrator safeguards)
// Method: POST (JSON)
// Body: {
//   "employee_id"?: "LCA123P",
//   "user_id"?: 27,
//   "ph_resolution"?: "unassign" | "fail" // when PH assignments exist
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

require_once __DIR__ . '/../../includes/config/database.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

if (!$auth->hasPermission('delete_users')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$employeeId    = $input['employee_id'] ?? null;
$targetUserId  = isset($input['user_id']) ? (int)$input['user_id'] : null;
$phResolution  = $input['ph_resolution'] ?? 'fail'; // default: fail if PH assignments exist

if (!$employeeId && !$targetUserId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Provide employee_id or user_id']);
    exit;
}

try {
    $pdo = Database::getInstance()->getConnection();

    // Resolve user and employee details
    if ($employeeId) {
        $stmt = $pdo->prepare("SELECT user_id FROM staff WHERE employee_number = ? LIMIT 1");
        $stmt->execute([$employeeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Staff not found']);
            exit;
        }
        $targetUserId = (int)$row['user_id'];
    } else {
        // Find a representative employee_number (if any)
        $stmt = $pdo->prepare("SELECT employee_number FROM staff WHERE user_id = ? LIMIT 1");
        $stmt->execute([$targetUserId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $employeeId = $row ? $row['employee_number'] : null;
    }

    // Fetch active staff rows for this user
    $stmt = $pdo->prepare("SELECT employee_number, staff_category, department_id, is_active FROM staff WHERE user_id = ? AND is_active = 1");
    $stmt->execute([$targetUserId]);
    $staffRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $hasPH = false; $phDepartments = [];
    $isSA  = false;
    foreach ($staffRows as $r) {
        $cat = strtolower(trim($r['staff_category'] ?? ''));
        if ($cat === 'program head') { $hasPH = true; if (!empty($r['department_id'])) $phDepartments[] = (int)$r['department_id']; }
        if ($cat === 'school administrator') { $isSA = true; }
    }

    // SA safety: ensure at least one other SA remains
    if ($isSA) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM staff WHERE staff_category = 'School Administrator' AND is_active = 1 AND user_id <> ?");
        $stmt->execute([$targetUserId]);
        $others = (int)$stmt->fetchColumn();
        if ($others === 0) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Cannot delete the only active School Administrator']);
            exit;
        }
    }

    // PH safety: require resolution or fail
    if ($hasPH && strtolower($phResolution) !== 'unassign') {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'Program Head has active department assignments',
            'departments' => $phDepartments
        ]);
        exit;
    }

    // Begin transaction for inactivation + soft delete
    $pdo->beginTransaction();

    // Unassign PH departments if requested
    if ($hasPH && strtolower($phResolution) === 'unassign') {
        $upd = $pdo->prepare("UPDATE staff SET is_active = 0, updated_at = NOW() WHERE user_id = ? AND staff_category = 'Program Head' AND is_active = 1");
        $upd->execute([$targetUserId]);
    }

    // Inactivate all staff rows for this user
    $updAll = $pdo->prepare("UPDATE staff SET is_active = 0, updated_at = NOW() WHERE user_id = ? AND is_active = 1");
    $updAll->execute([$targetUserId]);

    // Soft-delete user (status = deleted)
    $soft = $pdo->prepare("UPDATE users SET status = 'deleted', updated_at = NOW() WHERE user_id = ?");
    $soft->execute([$targetUserId]);

    $pdo->commit();

    // Audit
    require_once __DIR__ . '/../../includes/functions/audit_functions.php';
    logActivity($auth->getUserId(), 'Staff Delete', [
        'target_user_id' => $targetUserId,
        'employee_id'    => $employeeId,
        'ph_unassign'    => $hasPH ? ($phResolution === 'unassign') : false,
        'ph_departments' => $phDepartments
    ]);

    echo json_encode(['success' => true, 'message' => 'Staff deleted successfully']);
} catch (Exception $e) {
    if (!empty($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
// -----------------------------------------------------------------------------
// End of file
// -----------------------------------------------------------------------------
?>

