<?php
/**
 * API: Delete Program/Course
 * Method: POST/DELETE
 * Deletes a program (course) from the system
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
        echo json_encode(['success' => false, 'message' => 'Access denied. Only administrators can delete programs.']);
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

    // Extract program ID
    $programId = isset($input['program_id']) ? (int)$input['program_id'] : 0;

    if ($programId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Valid program ID is required.']);
        exit;
    }

    // Verify program exists
    $programCheckStmt = $pdo->prepare("
        SELECT p.program_id, p.program_name, p.program_code, p.department_id
        FROM programs p
        WHERE p.program_id = ?
    ");
    $programCheckStmt->execute([$programId]);
    $program = $programCheckStmt->fetch(PDO::FETCH_ASSOC);

    if (!$program) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Program not found.']);
        exit;
    }

    // Check if program has students enrolled
    $studentCheckStmt = $pdo->prepare("
        SELECT COUNT(*) as student_count 
        FROM students 
        WHERE program_id = ?
    ");
    $studentCheckStmt->execute([$programId]);
    $studentCount = (int)$studentCheckStmt->fetchColumn();

    if ($studentCount > 0) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => "Cannot delete program: {$studentCount} student(s) are enrolled in this program.",
            'student_count' => $studentCount,
            'program_name' => $program['program_name']
        ]);
        exit;
    }

    // Delete the program
    $deleteStmt = $pdo->prepare("DELETE FROM programs WHERE program_id = ?");
    $deleteStmt->execute([$programId]);

    echo json_encode([
        'success' => true,
        'message' => 'Program deleted successfully.',
        'program_id' => $programId,
        'program_name' => $program['program_name'],
        'program_code' => $program['program_code']
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
