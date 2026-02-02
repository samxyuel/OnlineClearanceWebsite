<?php
/**
 * API: Delete Department
 * Method: POST/DELETE
 * Deletes a department from the system (handles cross-sector departments)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
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
        echo json_encode(['success' => false, 'message' => 'Access denied. Only administrators can delete departments.']);
        exit;
    }

    if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'DELETE'], true)) {
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

    // Extract department ID
    $departmentId = isset($input['department_id']) ? (int)$input['department_id'] : 0;

    if ($departmentId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Valid department ID is required.']);
        exit;
    }

    // Verify department exists and get its code
    $deptCheckStmt = $pdo->prepare("
        SELECT department_id, department_name, department_code, sector_id 
        FROM departments 
        WHERE department_id = ?
    ");
    $deptCheckStmt->execute([$departmentId]);
    $department = $deptCheckStmt->fetch(PDO::FETCH_ASSOC);

    if (!$department) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Department not found.']);
        exit;
    }

    $departmentCode = $department['department_code'];
    $departmentName = $department['department_name'];

    // Find all departments with the same code (cross-sector departments)
    $allDepartmentsStmt = $pdo->prepare("
        SELECT department_id 
        FROM departments 
        WHERE department_code = ?
    ");
    $allDepartmentsStmt->execute([$departmentCode]);
    $allDepartmentIds = array_column($allDepartmentsStmt->fetchAll(PDO::FETCH_ASSOC), 'department_id');

    // Check if any of these departments have programs
    $programCheckStmt = $pdo->prepare("
        SELECT COUNT(*) as program_count 
        FROM programs 
        WHERE department_id IN (" . implode(',', array_fill(0, count($allDepartmentIds), '?')) . ")
    ");
    $programCheckStmt->execute($allDepartmentIds);
    $programCount = (int)$programCheckStmt->fetchColumn();

    if ($programCount > 0) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => "Cannot delete department: {$programCount} program(s) exist in this department.",
            'program_count' => $programCount,
            'department_name' => $departmentName
        ]);
        exit;
    }

    // Check if any of these departments have students
    $studentCheckStmt = $pdo->prepare("
        SELECT COUNT(*) as student_count 
        FROM students 
        WHERE department_id IN (" . implode(',', array_fill(0, count($allDepartmentIds), '?')) . ")
    ");
    $studentCheckStmt->execute($allDepartmentIds);
    $studentCount = (int)$studentCheckStmt->fetchColumn();

    if ($studentCount > 0) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => "Cannot delete department: {$studentCount} student(s) are assigned to this department.",
            'student_count' => $studentCount,
            'department_name' => $departmentName
        ]);
        exit;
    }

    // Check if any of these departments have staff (program heads, etc.)
    $staffCheckStmt = $pdo->prepare("
        SELECT COUNT(*) as staff_count 
        FROM staff 
        WHERE department_id IN (" . implode(',', array_fill(0, count($allDepartmentIds), '?')) . ")
    ");
    $staffCheckStmt->execute($allDepartmentIds);
    $staffCount = (int)$staffCheckStmt->fetchColumn();

    if ($staffCount > 0) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => "Cannot delete department: {$staffCount} staff member(s) are assigned to this department.",
            'staff_count' => $staffCount,
            'department_name' => $departmentName
        ]);
        exit;
    }

    // Start transaction to delete all related departments
    $pdo->beginTransaction();

    try {
        // Delete all departments with the same code (cross-sector)
        $deleteStmt = $pdo->prepare("DELETE FROM departments WHERE department_code = ?");
        $deleteStmt->execute([$departmentCode]);
        $deletedCount = $deleteStmt->rowCount();

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Department deleted successfully.',
            'deleted_records' => $deletedCount,
            'department_name' => $departmentName,
            'department_code' => $departmentCode,
            'is_cross_sector' => ($deletedCount > 1)
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
