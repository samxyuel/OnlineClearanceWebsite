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

    // Get all department types the Program Head is assigned to
    $deptStmt = $pdo->prepare("
        SELECT DISTINCT d.department_type 
        FROM user_department_assignments uda
        JOIN departments d ON uda.department_id = d.department_id
        WHERE uda.user_id = ? AND uda.is_active = 1
    ");
    $deptStmt->execute([$userId]);
    $assignedDeptTypes = $deptStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Normalize for comparison
    $assignedDeptTypesNormalized = array_map('strtolower', array_map('trim', $assignedDeptTypes));
    $clearanceTypeNormalized = strtolower(trim($clearanceType));

    // 2. Check if the requested clearance type is within the user's scope
    $hasDepartmentScope = in_array($clearanceTypeNormalized, $assignedDeptTypesNormalized);

    // If the clearance type is 'Faculty', a program head of a 'College' department should have scope.
    if ($clearanceTypeNormalized === 'faculty' && in_array('college', $assignedDeptTypesNormalized)) {
        $hasDepartmentScope = true;
    }

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
    error_log('is_assigned_debug: user_id=' . $userId . ' clearance_type=' . $clearanceType . ' designationId=' . $designationId . ' assigned_dept_types=' . json_encode($assignedDeptTypes) . ' has_scope=' . ($hasDepartmentScope ? '1' : '0') . ' includePhSetting=' . $includePhSetting . ' canTakeAction=' . ($canTakeAction ? '1' : '0'));

    echo json_encode([
        "success" => true,
        "can_take_action" => $canTakeAction,
        "debug" => [
            "assigned_department_types" => $assignedDeptTypes,
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
