<?php
// api/program-head/is_assigned.php
// Returns whether the current logged-in user (Program Head) can take signatory actions
// for a given clearance_type (e.g., 'Senior High School', 'College', 'Faculty').

if (session_status() == PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/config/database.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

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
    // 1. Get staff info from Auth context (designation_id, department_id)
    $staffStmt = $pdo->prepare("SELECT designation_id, department_id FROM staff WHERE user_id = ? AND is_active = 1 LIMIT 1");
    $staffStmt->execute([$userId]);
    $staff = $staffStmt->fetch(PDO::FETCH_ASSOC);

    if (!$staff) {
        error_log("is_assigned_debug: no staff row for user_id={$userId}");
        echo json_encode(["success" => true, "can_take_action" => false, "debug" => ["reason" => "no_staff"]]);
        exit;
    }

    $designationId = isset($staff['designation_id']) ? (int)$staff['designation_id'] : null;
    $departmentId = isset($staff['department_id']) ? (int)$staff['department_id'] : null;

    // 2. Get department_type
    $deptType = null;
    if ($departmentId) {
        $dstmt = $pdo->prepare("SELECT department_type FROM departments WHERE department_id = ? LIMIT 1");
        $dstmt->execute([$departmentId]);
        $deptType = $dstmt->fetchColumn();
    }

    // 3. Check if Program Head is enabled for this clearance_type via sector_clearance_settings
    $deptTypeNormalized = $deptType ? strtolower(trim($deptType)) : '';
    $clearanceTypeNormalized = strtolower(trim($clearanceType));

    $includePhSetting = 0;
    $settingStmt = $pdo->prepare("SELECT include_program_head FROM sector_clearance_settings WHERE clearance_type = ? LIMIT 1");
    $settingStmt->execute([$clearanceType]);
    $settingRow = $settingStmt->fetch(PDO::FETCH_ASSOC);
    if ($settingRow) {
        $includePhSetting = (int)$settingRow['include_program_head'];
    }

    // Permission: 
    // - Designation is Program Head (designation_id = 8, based on the schema pattern)
    // - Staff's department_type matches the requested clearance_type
    // - sector_clearance_settings.include_program_head = 1 for this clearance_type
    $isPhDesignation = ($designationId === 8); // Program Head designation
    $canTakeAction = ($isPhDesignation && ($deptTypeNormalized === $clearanceTypeNormalized) && ($includePhSetting === 1));

    // Log debug info to error log for tracing
    error_log('is_assigned_debug: user_id=' . $userId . ' clearance_type=' . $clearanceType . ' staff=' . json_encode($staff) . ' deptType=' . $deptType . ' designationId=' . $designationId . ' isPhDesignation=' . ($isPhDesignation ? '1' : '0') . ' deptTypeMatch=' . ($deptTypeNormalized === $clearanceTypeNormalized ? '1' : '0') . ' includePhSetting=' . $includePhSetting . ' canTakeAction=' . ($canTakeAction ? '1' : '0'));

    echo json_encode([
        "success" => true,
        "can_take_action" => (bool)$canTakeAction,
        "debug" => [
            "staff" => $staff,
            "department_type" => $deptType,
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
