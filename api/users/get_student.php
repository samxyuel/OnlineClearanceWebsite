<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');

require_once '../../includes/config/database.php';
require_once '../../includes/classes/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit;
}

if (!isset($_GET['user_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID is required.']);
    exit;
}

$userId = (int)$_GET['user_id'];

try {
    $pdo = Database::getInstance()->getConnection();

    $stmt = $pdo->prepare("
        SELECT
            u.user_id,
            u.first_name,
            u.last_name,
            u.middle_name,
            u.email,
            u.contact_number,
            u.account_status,
            s.student_id,
            s.sector,
            s.year_level,
            s.section,
            p.program_id,
            p.program_name,
            d.department_id,
            d.department_name
        FROM users u
        JOIN students s ON u.user_id = s.user_id
        LEFT JOIN programs p ON s.program_id = p.program_id
        LEFT JOIN departments d ON s.department_id = d.department_id
        WHERE u.user_id = ?
    ");
    $stmt->execute([$userId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student) {
        echo json_encode(['success' => true, 'student' => $student]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Student not found.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
