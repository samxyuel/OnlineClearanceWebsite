<?php
// API: Faculty Department Assignments
// GET: /api/faculty/department_assignments.php?user_id=123
// Methods: GET (fetch), POST (create), DELETE (remove)

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../includes/config/database.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

function json_resp($code, $payload) {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
} catch (Exception $e) {
    json_resp(500, ['success' => false, 'message' => 'Database connection failed']);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    // GET: Fetch faculty's department assignments
    $username = isset($_GET['user_id']) ? trim($_GET['user_id']) : null; // It's actually a username/employee_number
    if (!$username) {
        json_resp(400, ['success' => false, 'message' => 'user_id is required']);
    }

    try {
        // First, get the integer user_id from the users table
        $stmt_user = $db->prepare("SELECT user_id FROM users WHERE username = :username");
        $stmt_user->execute([':username' => $username]);
        $user = $stmt_user->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // To be safe, check faculty table as well, as the username in users might differ from employee_number
            $stmt_faculty = $db->prepare("SELECT user_id FROM faculty WHERE employee_number = :employee_number");
            $stmt_faculty->execute([':employee_number' => $username]);
            $user = $stmt_faculty->fetch(PDO::FETCH_ASSOC);
        }
        
        if (!$user) {
            json_resp(404, ['success' => false, 'message' => 'User not found']);
        }
        $userId = $user['user_id'];

        // Now, fetch the department assignments
        $stmt = $db->prepare("SELECT uda.department_assignment_id, uda.user_id, uda.department_id, uda.is_primary, 
                                    d.department_name, d.department_code, d.sector_id,
                                    s.sector_name
                             FROM user_department_assignments uda
                             JOIN departments d ON uda.department_id = d.department_id
                             LEFT JOIN sectors s ON d.sector_id = s.sector_id
                             WHERE uda.user_id = :user_id AND uda.is_active = 1
                             ORDER BY uda.is_primary DESC, d.department_name ASC");
        $stmt->execute([':user_id' => $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $departments = array_map(function($r){
            return [
                'assignment_id' => (int)$r['department_assignment_id'],
                'department_id' => (int)$r['department_id'],
                'department_name' => $r['department_name'],
                'department_code' => $r['department_code'],
                'sector_id' => $r['sector_id'] ? (int)$r['sector_id'] : null,
                'sector_name' => $r['sector_name'],
                'is_primary' => (bool)$r['is_primary']
            ];
        }, $rows);

        json_resp(200, ['success' => true, 'departments' => $departments]);
    } catch (PDOException $e) {
        json_resp(500, ['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

if ($method === 'POST') {
    // POST: Create new faculty department assignment
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        json_resp(400, ['success' => false, 'message' => 'Invalid JSON body']);
    }

    $userId = $input['user_id'] ?? null;
    $departmentId = $input['department_id'] ?? null;
    $isPrimary = $input['is_primary'] ?? false;

    if (!$userId || !$departmentId) {
        json_resp(400, ['success' => false, 'message' => 'user_id and department_id are required']);
    }

    try {
        // Check if assignment already exists
        $stmt = $db->prepare("SELECT 1 FROM user_department_assignments WHERE user_id = ? AND department_id = ?");
        $stmt->execute([$userId, $departmentId]);
        if ($stmt->fetch()) {
            json_resp(200, ['success' => false, 'message' => 'Assignment already exists']);
            return;
        }

        // If setting as primary, unset other primary assignments
        if ($isPrimary) {
            $stmt = $db->prepare("UPDATE user_department_assignments SET is_primary = 0 WHERE user_id = ? AND is_active = 1");
            $stmt->execute([$userId]);
        }

        // Create new assignment
        $stmt = $db->prepare("INSERT INTO user_department_assignments (user_id, department_id, is_primary, is_active) 
                              VALUES (?, ?, ?, 1)");
        $stmt->execute([$userId, $departmentId, $isPrimary ? 1 : 0]);

        json_resp(201, ['success' => true, 'message' => 'Assignment created successfully']);
    } catch (PDOException $e) {
        json_resp(500, ['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

if ($method === 'DELETE') {
    // DELETE: Remove faculty department assignment
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        json_resp(400, ['success' => false, 'message' => 'Invalid JSON body']);
    }

    $userId = $input['user_id'] ?? null;
    $departmentId = $input['department_id'] ?? null;

    if (!$userId || !$departmentId) {
        json_resp(400, ['success' => false, 'message' => 'user_id and department_id are required']);
    }

    try {
        $stmt = $db->prepare("DELETE FROM user_department_assignments WHERE user_id = ? AND department_id = ?");
        $stmt->execute([$userId, $departmentId]);

        json_resp(200, ['success' => true, 'message' => 'Assignment removed successfully']);
    } catch (PDOException $e) {
        json_resp(500, ['success' => false, 'message' => 'Database error']);
    }
}

json_resp(405, ['success' => false, 'message' => 'Method not allowed']);
?>
