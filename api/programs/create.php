<?php
/**
 * API: Create Program/Course
 * Method: POST
 * Creates a new program (course) in the specified department
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
        echo json_encode(['success' => false, 'message' => 'Access denied. Only administrators can create programs.']);
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
    $code = trim($input['code'] ?? '');
    $name = trim($input['name'] ?? '');
    $departmentId = isset($input['department']) ? (int)$input['department'] : 0;
    $status = $input['status'] ?? 'active';
    $isActive = ($status === 'active') ? 1 : 0;

    // Validate required fields
    if (empty($code)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Program code is required.']);
        exit;
    }

    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Program name is required.']);
        exit;
    }

    if ($departmentId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Valid department is required.']);
        exit;
    }

    // Verify department exists
    $deptCheckStmt = $pdo->prepare("SELECT department_id, sector_id FROM departments WHERE department_id = ? AND is_active = 1");
    $deptCheckStmt->execute([$departmentId]);
    $department = $deptCheckStmt->fetch(PDO::FETCH_ASSOC);

    if (!$department) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Department not found or inactive.']);
        exit;
    }

    // Verify department is course-eligible (College or SHS only, not Faculty-only)
    $sectorId = (int)$department['sector_id'];
    if ($sectorId === 3) {
        // This is a Faculty-only department
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cannot add courses to Faculty-only departments.']);
        exit;
    }

    // Check if program code already exists
    $codeCheckStmt = $pdo->prepare("SELECT program_id, program_name FROM programs WHERE program_code = ?");
    $codeCheckStmt->execute([$code]);
    $existingProgram = $codeCheckStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingProgram) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => "Program code '$code' already exists.",
            'existing_program' => $existingProgram
        ]);
        exit;
    }

    // Insert new program
    $insertStmt = $pdo->prepare("
        INSERT INTO programs (program_name, program_code, department_id, is_active, created_at, updated_at)
        VALUES (?, ?, ?, ?, NOW(), NOW())
    ");

    $insertStmt->execute([$name, $code, $departmentId, $isActive]);
    $programId = $pdo->lastInsertId();

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Program created successfully.',
        'program_id' => (int)$programId,
        'program_code' => $code,
        'program_name' => $name
    ]);

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
