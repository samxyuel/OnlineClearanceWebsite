<?php
/**
 * API: Year Level Retention Management - Dynamic API for RetainYearLevelSelectionModal
 * 
 * This endpoint manages year level retention selections for students.
 * Students selected for retention will keep their current year level when 
 * a new school year is created.
 * 
 * GET: Fetch all active students for retention selection
 * POST: Save retention selections (set retain_year_level_for_next_year = TRUE)
 * DELETE: Remove retention selections (set retain_year_level_for_next_year = FALSE)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

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

    // Route based on HTTP method
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        // ============================================
        // GET: Fetch All Active Students for Retention Selection
        // ============================================
        
        // Get query parameters
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? min(100, max(5, (int)$_GET['limit'])) : 50;
        $offset = ($page - 1) * $limit;
        $search = $_GET['search'] ?? '';
        $sector = $_GET['sector'] ?? '';
        $departmentId = $_GET['department_id'] ?? '';
        $programId = $_GET['program_id'] ?? '';
        $yearLevel = $_GET['year_level'] ?? '';

        // Build where conditions - only active students
        $whereConditions = [
            "u.account_status = 'active'"
        ];
        $params = [];

        // Add sector filter
        if (!empty($sector)) {
            $whereConditions[] = "s.sector = ?";
            $params[] = $sector;
        }

        // Add department filter
        if (!empty($departmentId)) {
            $whereConditions[] = "s.department_id = ?";
            $params[] = (int)$departmentId;
        }

        // Add program filter
        if (!empty($programId)) {
            $whereConditions[] = "s.program_id = ?";
            $params[] = (int)$programId;
        }

        // Add year level filter (excluding 4th Year as per requirement)
        if (!empty($yearLevel)) {
            $whereConditions[] = "s.year_level = ?";
            $params[] = $yearLevel;
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
        
        error_log("Count SQL: " . $countSql);
        error_log("Count Params: " . json_encode($params));
        
        $countStmt = $pdo->prepare($countSql);
        if (!$countStmt) {
            $errorInfo = $pdo->errorInfo();
            error_log("Count SQL Prepare Error: " . json_encode($errorInfo));
            throw new Exception("Failed to prepare count query: " . $errorInfo[2]);
        }
        
        $countStmt->execute($params);
        if ($countStmt->errorCode() !== '00000') {
            $errorInfo = $countStmt->errorInfo();
            error_log("Count SQL Execute Error: " . json_encode($errorInfo));
            throw new Exception("Failed to execute count query: " . $errorInfo[2]);
        }
        
        $total = $countStmt->fetchColumn();

        // Check if retain_year_level_for_next_year column exists
        $checkColumn = $pdo->query("
            SELECT COUNT(*) 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'students' 
            AND COLUMN_NAME = 'retain_year_level_for_next_year'
        ");
        $columnExists = $checkColumn->fetchColumn() > 0;
        
        // Get students with detailed information including retention status
        // Use COALESCE to handle missing column gracefully
        $retentionColumn = $columnExists 
            ? "s.retain_year_level_for_next_year" 
            : "0 as retain_year_level_for_next_year";
        
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
                {$retentionColumn},
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

        // Create a copy of params for the main query (with limit/offset)
        $mainParams = array_merge($params, [(int)$limit, (int)$offset]);

        error_log("Main SQL: " . $sql);
        error_log("Main Params: " . json_encode($mainParams));
        
        $stmt = $pdo->prepare($sql);
        if (!$stmt) {
            $errorInfo = $pdo->errorInfo();
            error_log("Main SQL Prepare Error: " . json_encode($errorInfo));
            throw new Exception("Failed to prepare main query: " . $errorInfo[2]);
        }
        
        $stmt->execute($mainParams);
        if ($stmt->errorCode() !== '00000') {
            $errorInfo = $stmt->errorInfo();
            error_log("Main SQL Execute Error: " . json_encode($errorInfo));
            throw new Exception("Failed to execute main query: " . $errorInfo[2]);
        }
        
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get statistics
        $retentionCountExpr = $columnExists 
            ? "COUNT(CASE WHEN s.retain_year_level_for_next_year = 1 THEN 1 END)" 
            : "0";
        
        $statsSql = "
            SELECT 
                COUNT(*) as total_students,
                {$retentionCountExpr} as retained_count,
                COUNT(CASE WHEN s.sector = 'College' THEN 1 END) as college_count,
                COUNT(CASE WHEN s.sector = 'Senior High School' THEN 1 END) as shs_count
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            WHERE u.account_status = 'active'
        ";
        $statsStmt = $pdo->prepare($statsSql);
        $statsStmt->execute();
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
        
        // If column doesn't exist, add a warning to the response
        if (!$columnExists) {
            error_log("WARNING: Column 'retain_year_level_for_next_year' does not exist in 'students' table. Please run: ALTER TABLE students ADD COLUMN retain_year_level_for_next_year BOOLEAN DEFAULT FALSE;");
        }

        send_json_response(true, [
            'students' => $students,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit),
            'stats' => $stats,
            'filters' => [
                'sector' => $sector,
                'department_id' => $departmentId,
                'program_id' => $programId,
                'year_level' => $yearLevel,
                'search' => $search
            ]
        ], "Retrieved {$total} students for retention selection.");

    } elseif ($method === 'POST') {
        // ============================================
        // POST: Save Retention Selections
        // ============================================
        
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            send_json_response(false, [], 'Invalid JSON input.', 400);
        }

        // Validate required fields
        if (!isset($input['student_ids']) || !is_array($input['student_ids']) || empty($input['student_ids'])) {
            send_json_response(false, [], 'Student IDs are required.', 400);
        }

        $studentIds = $input['student_ids'];

        // Validate that all student IDs are valid integers
        foreach ($studentIds as $studentId) {
            if (!is_numeric($studentId) || $studentId <= 0) {
                send_json_response(false, [], 'Invalid student ID format.', 400);
            }
        }

        // Start transaction
        $pdo->beginTransaction();

        try {
            // Verify all students exist and are active
            $placeholders = str_repeat('?,', count($studentIds) - 1) . '?';
            $verifyStmt = $pdo->prepare("
                SELECT s.student_id, s.user_id, s.year_level, s.sector,
                       u.first_name, u.last_name, u.account_status
                FROM students s
                JOIN users u ON s.user_id = u.user_id
                WHERE s.user_id IN ($placeholders) AND u.account_status = 'active'
            ");
            $verifyStmt->execute($studentIds);
            $verifiedStudents = $verifyStmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($verifiedStudents) !== count($studentIds)) {
                $pdo->rollBack();
                send_json_response(false, [], 'Some students were not found or are not active.', 400);
            }

            // Check if column exists before updating
            $checkColumn = $pdo->query("
                SELECT COUNT(*) 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'students' 
                AND COLUMN_NAME = 'retain_year_level_for_next_year'
            ");
            $columnExists = $checkColumn->fetchColumn() > 0;
            
            if (!$columnExists) {
                $pdo->rollBack();
                send_json_response(false, [], 'Database column "retain_year_level_for_next_year" does not exist. Please run: ALTER TABLE students ADD COLUMN retain_year_level_for_next_year BOOLEAN DEFAULT FALSE;', 500);
            }

            // Set retention flag to TRUE for selected students
            $updateStmt = $pdo->prepare("
                UPDATE students 
                SET retain_year_level_for_next_year = TRUE, updated_at = NOW()
                WHERE user_id IN ($placeholders)
            ");
            $updateStmt->execute($studentIds);
            $updatedCount = $updateStmt->rowCount();

            // Log the retention selection for each student
            $updatedStudents = [];
            foreach ($verifiedStudents as $student) {
                $logStmt = $pdo->prepare("
                    INSERT INTO user_activities (user_id, activity_type, activity_details, ip_address, user_agent) 
                    VALUES (?, 'year_level_retention_set', ?, ?, ?)
                ");
                $logStmt->execute([
                    $student['user_id'],
                    json_encode([
                        'action' => 'retention_selected',
                        'student_id' => $student['student_id'],
                        'student_name' => $student['first_name'] . ' ' . $student['last_name'],
                        'year_level' => $student['year_level'],
                        'sector' => $student['sector'],
                        'updated_by' => $userId,
                        'timestamp' => date('Y-m-d H:i:s')
                    ]),
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ]);

                $updatedStudents[] = [
                    'student_id' => $student['student_id'],
                    'name' => $student['first_name'] . ' ' . $student['last_name'],
                    'year_level' => $student['year_level'],
                    'sector' => $student['sector']
                ];
            }

            // Commit transaction
            $pdo->commit();

            // Log admin activity
            $adminLogStmt = $pdo->prepare("
                INSERT INTO user_activities (user_id, activity_type, activity_details, ip_address, user_agent) 
                VALUES (?, 'bulk_retention_update', ?, ?, ?)
            ");
            $adminLogStmt->execute([
                $userId,
                json_encode([
                    'action' => 'retention_selected',
                    'student_count' => $updatedCount,
                    'student_ids' => $studentIds,
                    'timestamp' => date('Y-m-d H:i:s')
                ]),
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);

            send_json_response(true, [
                'updated_count' => $updatedCount,
                'students' => $updatedStudents
            ], "Successfully set retention for {$updatedCount} student(s).");

        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }

    } elseif ($method === 'DELETE') {
        // ============================================
        // DELETE: Remove Retention Selections
        // ============================================
        
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            send_json_response(false, [], 'Invalid JSON input.', 400);
        }

        // Validate required fields
        if (!isset($input['student_ids']) || !is_array($input['student_ids']) || empty($input['student_ids'])) {
            send_json_response(false, [], 'Student IDs are required.', 400);
        }

        $studentIds = $input['student_ids'];

        // Validate that all student IDs are valid integers
        foreach ($studentIds as $studentId) {
            if (!is_numeric($studentId) || $studentId <= 0) {
                send_json_response(false, [], 'Invalid student ID format.', 400);
            }
        }

        // Start transaction
        $pdo->beginTransaction();

        try {
            // Verify all students exist
            $placeholders = str_repeat('?,', count($studentIds) - 1) . '?';
            $verifyStmt = $pdo->prepare("
                SELECT s.student_id, s.user_id, s.year_level, s.sector,
                       u.first_name, u.last_name
                FROM students s
                JOIN users u ON s.user_id = u.user_id
                WHERE s.user_id IN ($placeholders)
            ");
            $verifyStmt->execute($studentIds);
            $verifiedStudents = $verifyStmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($verifiedStudents) !== count($studentIds)) {
                $pdo->rollBack();
                send_json_response(false, [], 'Some students were not found.', 400);
            }

            // Check if column exists before updating
            $checkColumn = $pdo->query("
                SELECT COUNT(*) 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'students' 
                AND COLUMN_NAME = 'retain_year_level_for_next_year'
            ");
            $columnExists = $checkColumn->fetchColumn() > 0;
            
            if (!$columnExists) {
                $pdo->rollBack();
                send_json_response(false, [], 'Database column "retain_year_level_for_next_year" does not exist. Please run: ALTER TABLE students ADD COLUMN retain_year_level_for_next_year BOOLEAN DEFAULT FALSE;', 500);
            }
            
            // Set retention flag to FALSE for selected students
            $updateStmt = $pdo->prepare("
                UPDATE students 
                SET retain_year_level_for_next_year = FALSE, updated_at = NOW()
                WHERE user_id IN ($placeholders)
            ");
            $updateStmt->execute($studentIds);
            $updatedCount = $updateStmt->rowCount();

            // Log the removal for each student
            $updatedStudents = [];
            foreach ($verifiedStudents as $student) {
                $logStmt = $pdo->prepare("
                    INSERT INTO user_activities (user_id, activity_type, activity_details, ip_address, user_agent) 
                    VALUES (?, 'year_level_retention_removed', ?, ?, ?)
                ");
                $logStmt->execute([
                    $student['user_id'],
                    json_encode([
                        'action' => 'retention_removed',
                        'student_id' => $student['student_id'],
                        'student_name' => $student['first_name'] . ' ' . $student['last_name'],
                        'year_level' => $student['year_level'],
                        'sector' => $student['sector'],
                        'updated_by' => $userId,
                        'timestamp' => date('Y-m-d H:i:s')
                    ]),
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ]);

                $updatedStudents[] = [
                    'student_id' => $student['student_id'],
                    'name' => $student['first_name'] . ' ' . $student['last_name'],
                    'year_level' => $student['year_level'],
                    'sector' => $student['sector']
                ];
            }

            // Commit transaction
            $pdo->commit();

            // Log admin activity
            $adminLogStmt = $pdo->prepare("
                INSERT INTO user_activities (user_id, activity_type, activity_details, ip_address, user_agent) 
                VALUES (?, 'bulk_retention_removal', ?, ?, ?)
            ");
            $adminLogStmt->execute([
                $userId,
                json_encode([
                    'action' => 'retention_removed',
                    'student_count' => $updatedCount,
                    'student_ids' => $studentIds,
                    'timestamp' => date('Y-m-d H:i:s')
                ]),
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);

            send_json_response(true, [
                'updated_count' => $updatedCount,
                'students' => $updatedStudents
            ], "Successfully removed retention for {$updatedCount} student(s).");

        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }

    } else {
        // Unsupported HTTP method
        send_json_response(false, [], 'Method not allowed. Use GET to fetch students, POST to save retention, or DELETE to remove retention.', 405);
    }

} catch (PDOException $e) {
    error_log("Database error in year_level_retention.php: " . $e->getMessage());
    error_log("PDO Error Code: " . $e->getCode());
    error_log("PDO Error Info: " . json_encode($e->errorInfo ?? []));
    error_log("Stack trace: " . $e->getTraceAsString());
    send_json_response(false, [], 'Database error occurred: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    error_log("Error in year_level_retention.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    send_json_response(false, [], 'An error occurred while processing the request: ' . $e->getMessage(), 500);
}
?>

