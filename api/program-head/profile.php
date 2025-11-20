<?php
/**
 * API: Program Head - Profile
 *
 * Fetches the profile and department assignments for the logged-in Program Head.
 * This is used to populate UI elements like the "Add Student" modal.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/config/database.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

function send_json_response($success, $data = [], $message = '', $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit;
}

try {
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        send_json_response(false, [], 'Authentication required.', 401);
    }

    $userId = $auth->getUserId();
    $pdo = Database::getInstance()->getConnection();

    $deptStmt = $pdo->prepare("
        SELECT d.department_id, d.department_name
        FROM staff s
        JOIN departments d ON s.department_id = d.department_id
        WHERE s.user_id = ? AND s.staff_category = 'Program Head' AND s.is_active = 1
    ");
    $deptStmt->execute([$userId]);
    $departments = $deptStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Always return a consistent data structure, even if departments are empty.
    // This prevents JSON parsing errors on the frontend if a user has no assigned departments.
    $data = [
        'departments' => $departments,
        'is_program_head' => !empty($departments)
    ];
    
    send_json_response(true, $data);
} catch (Exception $e) {
    send_json_response(false, [], 'Server Error: ' . $e->getMessage(), 500);
}
?>
