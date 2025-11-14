<?php
/**
 * API: Graduation Management - Dynamic API for EligibleForGraduationModal
 * 
 * This endpoint combines the functionality of get_eligible_students.php and
 * update_graduation_status.php into a single dynamic API endpoint.
 * 
 * GET: Fetch eligible students for graduation
 * POST: Update graduation status for selected students
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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
        // GET: Fetch Eligible Students
        // ============================================
        
        // Get query parameters
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? min(100, max(5, (int)$_GET['limit'])) : 50;
        $offset = ($page - 1) * $limit;
        $search = $_GET['search'] ?? '';
        $sector = $_GET['sector'] ?? '';
        $departmentId = $_GET['department_id'] ?? '';
        $programId = $_GET['program_id'] ?? '';
        $yearLevel = $_GET['year_level'] ?? ''; // Optional - only used for eligible students
        $accountStatus = $_GET['account_status'] ?? 'active'; // Default to 'active' for eligible students, 'graduated' for graduated list
        $includeFilters = isset($_GET['include_filters']);

        // Build where conditions
        // Note: enrollment_status column doesn't exist in students table
        // We filter by account_status in users table instead
        $whereConditions = [
            "u.account_status = ?"
        ];
        $params = [$accountStatus];
        
        // Only filter by year_level if provided (for eligible students modal)
        // For graduated students list, we want all graduated students regardless of year level
        if ($yearLevel) {
            $whereConditions[] = "s.year_level = ?";
            $params[] = $yearLevel;
        }

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

        // Add program filter
        if ($programId) {
            $whereConditions[] = "s.program_id = ?";
            $params[] = $programId;
        }

        // Add search filter
        if ($search) {
            $whereConditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR s.student_id LIKE ? OR u.middle_name LIKE ?)";
            $searchTerm = "%{$search}%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }

        $whereClause = "WHERE " . implode(" AND ", $whereConditions);

        // Debug: Log the query and parameters
        error_log("Graduation Management Query - Account Status: {$accountStatus}, Year Level: {$yearLevel}, Sector: {$sector}");
        error_log("Where Clause: {$whereClause}");
        error_log("Params: " . print_r($params, true));

        // Get total count (without LIMIT/OFFSET)
        $countSql = "
            SELECT COUNT(*) 
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            LEFT JOIN programs p ON s.program_id = p.program_id
            LEFT JOIN departments d ON s.department_id = d.department_id
            {$whereClause}
        ";
        
        try {
            $countStmt = $pdo->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();
            error_log("Total students found: {$total}");
        } catch (PDOException $e) {
            error_log("Error in count query: " . $e->getMessage());
            error_log("SQL: {$countSql}");
            error_log("Params: " . print_r($params, true));
            throw $e;
        }

        // Get students with detailed information
        // Note: LIMIT and OFFSET need to be added to params array
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
            ORDER BY u.last_name ASC, u.first_name ASC
            LIMIT ? OFFSET ?
        ";

        // Add LIMIT and OFFSET to params
        $queryParams = array_merge($params, [(int)$limit, (int)$offset]);

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($queryParams);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Students retrieved: " . count($students));
            if (count($students) > 0) {
                error_log("Sample student: " . print_r($students[0], true));
            }
        } catch (PDOException $e) {
            error_log("Error in student query: " . $e->getMessage());
            error_log("SQL: {$sql}");
            error_log("Params: " . print_r($queryParams, true));
            throw $e;
        }
        
        if (count($students) === 0) {
            // Check what year levels actually exist for this sector and account status
            $checkYearLevels = $pdo->prepare("
                SELECT DISTINCT s.year_level, COUNT(*) as count
                FROM students s
                JOIN users u ON s.user_id = u.user_id
                WHERE s.sector = ? AND u.account_status = ?
                GROUP BY s.year_level
            ");
            $checkYearLevels->execute([$sector, $accountStatus]);
            $existingYearLevels = $checkYearLevels->fetchAll(PDO::FETCH_ASSOC);
            error_log("Existing year levels for {$sector} with account_status={$accountStatus}: " . print_r($existingYearLevels, true));
        }

        $filtersAvailable = [
            'departments' => [],
            'programs' => [],
            'year_levels' => []
        ];

        if ($includeFilters) {
            $filtersParams = $params;

            $deptSql = "
                SELECT DISTINCT s.department_id, COALESCE(d.department_name, 'Unassigned Department') AS department_name
                FROM students s
                JOIN users u ON s.user_id = u.user_id
                LEFT JOIN departments d ON s.department_id = d.department_id
                {$whereClause}
                ORDER BY department_name ASC
            ";
            $deptStmt = $pdo->prepare($deptSql);
            $deptStmt->execute($filtersParams);
            $filtersAvailable['departments'] = array_map(function ($row) {
                return [
                    'value' => (string) ($row['department_id'] ?? ''),
                    'label' => $row['department_name'] ?? 'Unassigned Department'
                ];
            }, $deptStmt->fetchAll(PDO::FETCH_ASSOC));

            $programSql = "
                SELECT DISTINCT s.program_id, COALESCE(p.program_name, 'Unassigned Program') AS program_name
                FROM students s
                JOIN users u ON s.user_id = u.user_id
                LEFT JOIN programs p ON s.program_id = p.program_id
                {$whereClause}
                ORDER BY program_name ASC
            ";
            $programStmt = $pdo->prepare($programSql);
            $programStmt->execute($filtersParams);
            $filtersAvailable['programs'] = array_map(function ($row) {
                return [
                    'value' => (string) ($row['program_id'] ?? ''),
                    'label' => $row['program_name'] ?? 'Unassigned Program'
                ];
            }, $programStmt->fetchAll(PDO::FETCH_ASSOC));

            $yearLevelSql = "
                SELECT DISTINCT s.year_level
                FROM students s
                JOIN users u ON s.user_id = u.user_id
                {$whereClause}
                ORDER BY s.year_level ASC
            ";
            $yearStmt = $pdo->prepare($yearLevelSql);
            $yearStmt->execute($filtersParams);
            $filtersAvailable['year_levels'] = array_map(function ($row) {
                return $row['year_level'];
            }, $yearStmt->fetchAll(PDO::FETCH_ASSOC));
        }

        // Get statistics (only for eligible students, not for graduated list)
        $stats = null;
        if ($accountStatus === 'active' && $yearLevel) {
            $statsSql = "
                SELECT 
                    COUNT(*) as total_eligible,
                    COUNT(CASE WHEN u.account_status = 'active' THEN 1 END) as active,
                    COUNT(CASE WHEN u.account_status = 'graduated' THEN 1 END) as graduated,
                    COUNT(CASE WHEN s.sector = 'College' THEN 1 END) as college_count,
                    COUNT(CASE WHEN s.sector = 'Senior High School' THEN 1 END) as shs_count
                FROM students s
                JOIN users u ON s.user_id = u.user_id
                WHERE s.year_level = ? AND u.account_status = 'active'
            ";
            $statsStmt = $pdo->prepare($statsSql);
            $statsStmt->execute([$yearLevel]);
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
        }

        send_json_response(true, [
            'students' => $students,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit),
            'stats' => $stats,
            'filters_available' => $filtersAvailable,
            'filters' => [
                'year_level' => $yearLevel,
                'sector' => $sector,
                'department_id' => $departmentId,
                'program_id' => $programId,
                'search' => $search
            ]
        ], "Retrieved {$total} eligible students.");

    } elseif ($method === 'POST') {
        // ============================================
        // POST: Update Graduation Status
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

        if (!isset($input['action']) || !in_array($input['action'], ['graduate', 'retain'])) {
            send_json_response(false, [], 'Valid action (graduate/retain) is required.', 400);
        }

        $studentIds = $input['student_ids'];
        $action = $input['action'];

        // Validate that all student IDs are valid integers
        foreach ($studentIds as $studentId) {
            if (!is_numeric($studentId) || $studentId <= 0) {
                send_json_response(false, [], 'Invalid student ID format.', 400);
            }
        }

        // Start transaction
        $pdo->beginTransaction();

        try {
            // Verify all students exist and are eligible (2nd Year for SHS, 4th Year for College)
            $placeholders = str_repeat('?,', count($studentIds) - 1) . '?';
            $verifyStmt = $pdo->prepare("
                SELECT s.student_id, s.user_id, s.year_level, s.sector,
                       u.first_name, u.last_name, u.account_status
                FROM students s
                JOIN users u ON s.user_id = u.user_id
                WHERE s.user_id IN ($placeholders) 
                AND u.account_status = 'active'
                AND (s.year_level = '2nd Year' OR s.year_level = '4th Year')
            ");
            $verifyStmt->execute($studentIds);
            $verifiedStudents = $verifyStmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($verifiedStudents) !== count($studentIds)) {
                $pdo->rollBack();
                send_json_response(false, [], 'Some students were not found or are not eligible for graduation.', 400);
            }

            $updatedCount = 0;
            $updatedStudents = [];

            if ($action === 'graduate') {
                // Mark students as graduated by updating account_status in users table
                $updateStmt = $pdo->prepare("
                    UPDATE users 
                    SET account_status = 'graduated', updated_at = NOW()
                    WHERE user_id IN ($placeholders)
                ");
                $updateStmt->execute($studentIds);
                $updatedCount = $updateStmt->rowCount();

                // Log the graduation for each student
                foreach ($verifiedStudents as $student) {
                    $logStmt = $pdo->prepare("
                        INSERT INTO user_activities (user_id, activity_type, activity_details, ip_address, user_agent) 
                        VALUES (?, 'graduation_status_updated', ?, ?, ?)
                    ");
                    $logStmt->execute([
                        $student['user_id'],
                        json_encode([
                            'action' => 'graduated',
                            'student_id' => $student['student_id'],
                            'student_name' => $student['first_name'] . ' ' . $student['last_name'],
                            'updated_by' => $userId,
                            'timestamp' => date('Y-m-d H:i:s')
                        ]),
                        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                    ]);

                    $updatedStudents[] = [
                        'student_id' => $student['student_id'],
                        'name' => $student['first_name'] . ' ' . $student['last_name'],
                        'status' => 'Graduated'
                    ];
                }

            } elseif ($action === 'retain') {
                // Keep students active (no status change, just log the action)
                foreach ($verifiedStudents as $student) {
                    $logStmt = $pdo->prepare("
                        INSERT INTO user_activities (user_id, activity_type, activity_details, ip_address, user_agent) 
                        VALUES (?, 'graduation_status_updated', ?, ?, ?)
                    ");
                    $logStmt->execute([
                        $student['user_id'],
                        json_encode([
                            'action' => 'retained',
                            'student_id' => $student['student_id'],
                            'student_name' => $student['first_name'] . ' ' . $student['last_name'],
                            'updated_by' => $userId,
                            'timestamp' => date('Y-m-d H:i:s')
                        ]),
                        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                    ]);

                    $updatedStudents[] = [
                        'student_id' => $student['student_id'],
                        'name' => $student['first_name'] . ' ' . $student['last_name'],
                        'status' => 'Retained'
                    ];
                }
                $updatedCount = count($verifiedStudents);
            }

            // Commit transaction
            $pdo->commit();

            // Log admin activity
            $adminLogStmt = $pdo->prepare("
                INSERT INTO user_activities (user_id, activity_type, activity_details, ip_address, user_agent) 
                VALUES (?, 'bulk_graduation_update', ?, ?, ?)
            ");
            $adminLogStmt->execute([
                $userId,
                json_encode([
                    'action' => $action,
                    'student_count' => $updatedCount,
                    'student_ids' => $studentIds,
                    'timestamp' => date('Y-m-d H:i:s')
                ]),
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);

            send_json_response(true, [
                'updated_count' => $updatedCount,
                'action' => $action,
                'students' => $updatedStudents
            ], "Successfully updated {$updatedCount} student(s) graduation status.");

        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }

    } else {
        // Unsupported HTTP method
        send_json_response(false, [], 'Method not allowed. Use GET to fetch students or POST to update status.', 405);
    }

} catch (PDOException $e) {
    error_log("Database error in graduation_management.php: " . $e->getMessage());
    error_log("SQL Error Code: " . $e->getCode());
    error_log("SQL Error Info: " . print_r($e->errorInfo ?? [], true));
    send_json_response(false, [], 'Database error occurred: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    error_log("Error in graduation_management.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    send_json_response(false, [], 'An error occurred while processing the request: ' . $e->getMessage(), 500);
}
?>

