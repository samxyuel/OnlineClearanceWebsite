<?php
/**
 * API: Update Department
 * Method: POST
 * Updates an existing department (handles cross-sector departments)
 */

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

try {
    $auth = new Auth();

    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required.']);
        exit;
    }

    $allowedRoles = ['Admin', 'School Administrator'];
    $userRole = $auth->getRoleName();

    if (!in_array($userRole, $allowedRoles, true)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied. Only administrators can update departments.']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input.']);
        exit;
    }

    $pdo = Database::getInstance()->getConnection();

    // Extract and validate input
    $departmentId = isset($input['id']) ? (int)$input['id'] : 0;
    $name = trim($input['name'] ?? '');
    $type = $input['type'] ?? '';
    $status = $input['status'] ?? 'active';
    $isActive = ($status === 'active') ? 1 : 0;

    // Validate required fields
    if ($departmentId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Valid department ID is required.']);
        exit;
    }

    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Department name is required.']);
        exit;
    }

    if (empty($type)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Department type is required.']);
        exit;
    }

    // Verify department exists and get its code
    $deptCheckStmt = $pdo->prepare("
        SELECT department_id, department_name, department_code, sector_id 
        FROM departments 
        WHERE department_id = ?
    ");
    $deptCheckStmt->execute([$departmentId]);
    $existingDept = $deptCheckStmt->fetch(PDO::FETCH_ASSOC);

    if (!$existingDept) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Department not found.']);
        exit;
    }

    $departmentCode = $existingDept['department_code'];

    // Map type to department_type enum
    $deptTypeMap = [
        'college' => 'College',
        'senior-high' => 'Senior High School',
        'faculty' => 'Faculty'
    ];

    $primaryDeptType = $deptTypeMap[$type] ?? 'College';

    // Find all departments with the same code (cross-sector departments)
    $allDepartmentsStmt = $pdo->prepare("
        SELECT department_id, sector_id 
        FROM departments 
        WHERE department_code = ?
    ");
    $allDepartmentsStmt->execute([$departmentCode]);
    $allDepartments = $allDepartmentsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Start transaction for cross-sector update
    $pdo->beginTransaction();

    try {
        $updatedCount = 0;

        // Update all departments with the same code
        foreach ($allDepartments as $dept) {
            $deptId = (int)$dept['department_id'];
            $sectorId = (int)$dept['sector_id'];
            
            // Use 'Faculty' type for sector_id = 3, otherwise use the selected type
            $deptType = ($sectorId === 3) ? 'Faculty' : $primaryDeptType;

            $updateStmt = $pdo->prepare("
                UPDATE departments 
                SET department_name = ?,
                    department_type = ?,
                    is_active = ?,
                    updated_at = NOW()
                WHERE department_id = ?
            ");

            $updateStmt->execute([$name, $deptType, $isActive, $deptId]);
            $updatedCount++;
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Department updated successfully.',
            'updated_records' => $updatedCount,
            'department_id' => $departmentId,
            'department_name' => $name,
            'is_cross_sector' => ($updatedCount > 1)
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
