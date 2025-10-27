<?php
/**
 * API: Get Eligible Students for Graduation
 * 
 * This endpoint fetches 4th Year students who are eligible for graduation.
 * It's specifically designed for the EligibleForGraduationModal.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');

require_once '../../includes/config/database.php';
require_once '../../includes/classes/Auth.php';

function send_json_response($success, $data = [], $message = '', $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit;
}

try {
    // Authentication check
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        send_json_response(false, [], 'Authentication required.', 401);
    }

    $userId = $auth->getUserId();
    $pdo = Database::getInstance()->getConnection();

    // Check if user has admin privileges
    $roleCheck = $pdo->prepare("
        SELECT r.role_name 
        FROM users u 
        JOIN user_roles ur ON u.user_id = ur.user_id 
        JOIN roles r ON ur.role_id = r.role_id 
        WHERE u.user_id = ? AND r.role_name IN ('Admin', 'School Administrator')
    ");
    $roleCheck->execute([$userId]);
    $hasAdminRole = $roleCheck->fetchColumn();
    
    if (!$hasAdminRole) {
        send_json_response(false, [], 'Admin access required.', 403);
    }

    // Get query parameters
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(5, (int)$_GET['limit'])) : 50;
    $offset = ($page - 1) * $limit;
    $search = $_GET['search'] ?? '';
    $sector = $_GET['sector'] ?? '';
    $departmentId = $_GET['department_id'] ?? '';
    $yearLevel = $_GET['year_level'] ?? '4th Year'; // Default to 4th Year for graduation eligibility
    $enrollmentStatus = $_GET['enrollment_status'] ?? 'Enrolled'; // Default to enrolled students

    // Build where conditions
    $whereConditions = [
        "u.account_status = 'active'",
        "s.year_level = ?",
        "s.enrollment_status = ?"
    ];
    $params = [$yearLevel, $enrollmentStatus];

    // Add sector filter
    if ($sector) {
        $whereConditions[] = "s.sector = ?";
        $params[] = $sector;
    }

    // Add department filter
    if ($departmentId) {
        $whereConditions[] = "s.department_id = ?";
        $params[] = $departmentId;
    }

    // Add search filter
    if ($search) {
        $whereConditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR s.student_id LIKE ? OR u.middle_name LIKE ?)";
        $searchTerm = "%{$search}%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }

    $whereClause = "WHERE " . implode(" AND ", $whereConditions);

    // Get total count
    $countSql = "
        SELECT COUNT(*) 
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        LEFT JOIN programs p ON s.program_id = p.program_id
        LEFT JOIN departments d ON s.department_id = d.department_id
        {$whereClause}
    ";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();

    // Get students with detailed information
    $sql = "
        SELECT 
            s.student_id,
            s.user_id,
            u.username,
            u.first_name,
            u.last_name,
            u.middle_name,
            u.email,
            u.account_status,
            s.sector,
            s.section,
            s.year_level,
            s.enrollment_status,
            p.program_name as program,
            d.department_name as department,
            d.department_id,
            s.created_at,
            s.updated_at
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        LEFT JOIN programs p ON s.program_id = p.program_id
        LEFT JOIN departments d ON s.department_id = d.department_id
        {$whereClause}
        ORDER BY s.created_at DESC
        LIMIT ? OFFSET ?
    ";

    $params[] = $limit;
    $params[] = $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get statistics
    $statsSql = "
        SELECT 
            COUNT(*) as total_eligible,
            COUNT(CASE WHEN s.enrollment_status = 'Enrolled' THEN 1 END) as enrolled,
            COUNT(CASE WHEN s.enrollment_status = 'Graduated' THEN 1 END) as graduated,
            COUNT(CASE WHEN s.sector = 'College' THEN 1 END) as college_count,
            COUNT(CASE WHEN s.sector = 'Senior High School' THEN 1 END) as shs_count
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        WHERE s.year_level = ? AND u.account_status = 'active'
    ";
    $statsStmt = $pdo->prepare($statsSql);
    $statsStmt->execute([$yearLevel]);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    send_json_response(true, [
        'students' => $students,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => ceil($total / $limit),
        'stats' => $stats,
        'filters' => [
            'year_level' => $yearLevel,
            'enrollment_status' => $enrollmentStatus,
            'sector' => $sector,
            'department_id' => $departmentId,
            'search' => $search
        ]
    ], "Retrieved {$total} eligible students.");

} catch (PDOException $e) {
    error_log("Database error in get_eligible_students.php: " . $e->getMessage());
    send_json_response(false, [], 'Database error occurred.', 500);
} catch (Exception $e) {
    error_log("Error in get_eligible_students.php: " . $e->getMessage());
    send_json_response(false, [], 'An error occurred while processing the request.', 500);
}
?>
