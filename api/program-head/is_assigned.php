<?php
// api/program-head/is_assigned.php
// Returns whether the current logged-in user (Program Head) can take signatory actions
// for a given clearance_type (e.g., 'Senior High School', 'College', 'Faculty').

if (session_status() == PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/config/database.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';
require_once __DIR__ . '/../../includes/helpers/department_helpers.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Not authenticated"]);
    exit;
}

$pdo = Database::getInstance()->getConnection();
$userId = (int)$auth->getUserId();

$clearanceType = isset($_GET['clearance_type']) ? trim($_GET['clearance_type']) : null;
if (!$clearanceType) {
    echo json_encode(["success" => false, "message" => "Missing clearance_type parameter"]);
    exit;
}

try {
    // 1. Get the user's primary designation and all assigned department IDs
    $staffStmt = $pdo->prepare("
        SELECT s.designation_id 
        FROM staff s 
        WHERE s.user_id = ? AND s.is_active = 1 
        LIMIT 1
    ");
    $staffStmt->execute([$userId]);
    $designationId = $staffStmt->fetchColumn();

    $isPhDesignation = ($designationId === 8); // 8 is typically 'Program Head'

    if (!$isPhDesignation) {
        echo json_encode(["success" => true, "can_take_action" => false, "debug" => ["reason" => "not_a_program_head"]]);
        exit;
    }

    // Get all department IDs across sectors using cross-sector matching
    $allDeptIds = getCrossSectorDepartmentIds($pdo, $userId);

    if (empty($allDeptIds)) {
        echo json_encode(["success" => true, "can_take_action" => false, "debug" => ["reason" => "no_departments_assigned"]]);
        exit;
    }

    // Check if any of these departments exist in the requested clearance type sector
    $placeholders = implode(',', array_fill(0, count($allDeptIds), '?'));
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM departments d
        JOIN sectors s ON d.sector_id = s.sector_id
        WHERE d.department_id IN ($placeholders)
        AND s.sector_name = ?
        AND d.is_active = 1
    ");
    $params = array_merge($allDeptIds, [$clearanceType]);
    $stmt->execute($params);
    $hasDepartmentScope = $stmt->fetchColumn() > 0;

    // 3. Check if Program Head is enabled for this clearance_type via sector_clearance_settings
    $includePhSetting = 0;
    $settingStmt = $pdo->prepare("SELECT include_program_head FROM sector_clearance_settings WHERE clearance_type = ? LIMIT 1");
    $settingStmt->execute([$clearanceType]);
    $settingRow = $settingStmt->fetch(PDO::FETCH_ASSOC);
    if ($settingRow) {
        $includePhSetting = (int)$settingRow['include_program_head'];
    }

    // Final permission check
    $canTakeAction = $hasDepartmentScope && ($includePhSetting === 1);

    // Log debug info
    error_log('is_assigned_debug: user_id=' . $userId . ' clearance_type=' . $clearanceType . ' designationId=' . $designationId . ' all_dept_ids=' . json_encode($allDeptIds) . ' has_scope=' . ($hasDepartmentScope ? '1' : '0') . ' includePhSetting=' . $includePhSetting . ' canTakeAction=' . ($canTakeAction ? '1' : '0'));

    echo json_encode([
        "success" => true,
        "can_take_action" => $canTakeAction,
        "debug" => [
            "cross_sector_department_ids" => $allDeptIds,
            "requested_clearance_type" => $clearanceType,
            "has_department_scope" => $hasDepartmentScope,
            "include_program_head_setting" => $includePhSetting
        ]
    ]);
    exit;
} catch (Throwable $e) {
    error_log('is_assigned_error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Server error"]);
    exit;
}
