<?php
/**
 * API: Batch Update End Users (Consolidated)
 * 
 * Handles bulk updates for all end users including:
 * - College Students: Year Level, Section, Program (Admin only)
 * - Senior High School Students: Year Level, Section, Program (Admin only)
 * - Faculty: Employment Status, Department (Admin only)
 * 
 * Permissions:
 * - Admin: Can update all fields for all sectors
 * - Program Head: Can only update Year Level and Section for students in their assigned departments
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once '../../includes/config/database.php';
require_once '../../includes/classes/Auth.php';

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'data' => [
        'updated' => [],
        'failed' => [],
        'total_processed' => 0,
        'success_count' => 0,
        'error_count' => 0
    ]
];

try {
    // Check authentication
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        throw new Exception('Authentication required', 401);
    }

    $userId = $auth->getUserId();
    $pdo = Database::getInstance()->getConnection();

    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON data', 400);
    }

    // Validate required fields
    if (!isset($input['student_ids']) || !is_array($input['student_ids']) || empty($input['student_ids'])) {
        throw new Exception('Student IDs are required', 400);
    }

    if (!isset($input['updates']) || !is_array($input['updates'])) {
        throw new Exception('Updates are required', 400);
    }

    $studentIds = $input['student_ids'];
    $updates = $input['updates'];
    $userRole = $auth->getUserRole();

    // Validate user permissions
    if (!in_array($userRole, ['Admin', 'Program Head'])) {
        throw new Exception('Insufficient permissions', 403);
    }

    // Determine sector from request or student data
    $sector = isset($input['sector']) ? $input['sector'] : 'College'; // Default to College for backward compatibility
    
    // Check if clearance period is active (prevent updates during active clearance)
    $activePeriodCheck = $pdo->prepare("
        SELECT COUNT(*) as active_count 
        FROM clearance_periods 
        WHERE clearance_type = ? 
        AND status = 'Ongoing' 
        AND is_active = 1
    ");
    $activePeriodCheck->execute([$sector]);
    $activePeriods = $activePeriodCheck->fetch(PDO::FETCH_ASSOC);

    if ($activePeriods['active_count'] > 0) {
        throw new Exception("Cannot update students during active clearance period for {$sector}", 403);
    }

    // Get user's department assignments if Program Head
    $userDepartments = [];
    if ($userRole === 'Program Head') {
        $deptStmt = $pdo->prepare("
            SELECT d.department_id 
            FROM staff s
            JOIN departments d ON s.department_id = d.department_id
            WHERE s.user_id = ? AND s.staff_category = 'Program Head' AND s.is_active = 1
        ");
        $deptStmt->execute([$userId]);
        $userDepartments = $deptStmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($userDepartments)) {
            throw new Exception('No departments assigned to this Program Head', 403);
        }
    }

    // Validate updates based on user role and sector
    $allowedUpdates = [];
    if ($userRole === 'Admin') {
        if ($sector === 'College' || $sector === 'Senior High School') {
            $allowedUpdates = ['year_level', 'section', 'program_id'];
        } elseif ($sector === 'Faculty') {
            $allowedUpdates = ['employment_status', 'department_id'];
        }
    } else {
        // Program Head can only update students
        if ($sector === 'College' || $sector === 'Senior High School') {
            $allowedUpdates = ['year_level', 'section'];
        } else {
            throw new Exception('Program Head can only update students', 403);
        }
    }

    $validUpdates = [];
    foreach ($updates as $field => $value) {
        if (in_array($field, $allowedUpdates) && $value !== '') {
            $validUpdates[$field] = $value;
        }
    }

    if (empty($validUpdates)) {
        throw new Exception('No valid updates provided', 400);
    }

    // Validate program_id if updating program
    if (isset($validUpdates['program_id'])) {
        $programCheck = $pdo->prepare("
            SELECT p.program_id, p.department_id 
            FROM programs p 
            WHERE p.program_id = ? AND p.is_active = 1
        ");
        $programCheck->execute([$validUpdates['program_id']]);
        $program = $programCheck->fetch(PDO::FETCH_ASSOC);
        
        if (!$program) {
            throw new Exception('Invalid program selected', 400);
        }
        
        // For Program Heads, ensure the program belongs to their assigned departments
        if ($userRole === 'Program Head' && !in_array($program['department_id'], $userDepartments)) {
            throw new Exception('Program not in your assigned departments', 403);
        }
    }

    // Start transaction
    $pdo->beginTransaction();

    $response['data']['total_processed'] = count($studentIds);

    foreach ($studentIds as $studentId) {
        try {
            // Verify user exists and get current data based on sector
            if ($sector === 'College' || $sector === 'Senior High School') {
                $studentStmt = $pdo->prepare("
                    SELECT s.user_id, s.department_id, u.first_name, u.last_name, u.username
                    FROM students s
                    JOIN users u ON s.user_id = u.user_id
                    WHERE s.user_id = ? AND s.sector = ?
                ");
                $studentStmt->execute([$studentId, $sector]);
                $user = $studentStmt->fetch(PDO::FETCH_ASSOC);
            } elseif ($sector === 'Faculty') {
                $facultyStmt = $pdo->prepare("
                    SELECT f.user_id, f.department_id, u.first_name, u.last_name, u.username
                    FROM faculty f
                    JOIN users u ON f.user_id = u.user_id
                    WHERE f.user_id = ?
                ");
                $facultyStmt->execute([$studentId]);
                $user = $facultyStmt->fetch(PDO::FETCH_ASSOC);
            }

            if (!$user) {
                $response['data']['failed'][] = [
                    'student_id' => $studentId,
                    'error' => 'User not found'
                ];
                $response['data']['error_count']++;
                continue;
            }

            // For Program Heads, verify user is in their assigned departments
            if ($userRole === 'Program Head' && !in_array($user['department_id'], $userDepartments)) {
                $response['data']['failed'][] = [
                    'student_id' => $studentId,
                    'student_name' => $user['first_name'] . ' ' . $user['last_name'],
                    'error' => 'User not in your assigned departments'
                ];
                $response['data']['error_count']++;
                continue;
            }

            // Build update query
            $updateFields = [];
            $updateParams = [];

            foreach ($validUpdates as $field => $value) {
                if ($field === 'program_id') {
                    // Get department_id from program
                    $deptStmt = $pdo->prepare("SELECT department_id FROM programs WHERE program_id = ?");
                    $deptStmt->execute([$value]);
                    $programDept = $deptStmt->fetch(PDO::FETCH_ASSOC);
                    
                    $updateFields[] = "program_id = ?";
                    $updateFields[] = "department_id = ?";
                    $updateParams[] = $value;
                    $updateParams[] = $programDept['department_id'];
                } else {
                    $updateFields[] = "$field = ?";
                    $updateParams[] = $value;
                }
            }

            $updateParams[] = $studentId;

            // Execute update based on sector
            if ($sector === 'College' || $sector === 'Senior High School') {
                $updateStmt = $pdo->prepare("
                    UPDATE students 
                    SET " . implode(', ', $updateFields) . " 
                    WHERE user_id = ?
                ");
            } elseif ($sector === 'Faculty') {
                $updateStmt = $pdo->prepare("
                    UPDATE faculty 
                    SET " . implode(', ', $updateFields) . " 
                    WHERE user_id = ?
                ");
            }
            
            $updateStmt->execute($updateParams);

            $response['data']['updated'][] = [
                'student_id' => $studentId,
                'student_name' => $user['first_name'] . ' ' . $user['last_name'],
                'updates' => $validUpdates
            ];
            $response['data']['success_count']++;

        } catch (Exception $e) {
            $response['data']['failed'][] = [
                'student_id' => $studentId,
                'error' => $e->getMessage()
            ];
            $response['data']['error_count']++;
        }
    }

    // Commit transaction
    $pdo->commit();

    $response['success'] = true;
    $response['message'] = "Batch update completed. {$response['data']['success_count']} students updated successfully.";

    if ($response['data']['error_count'] > 0) {
        $response['message'] .= " {$response['data']['error_count']} students failed to update.";
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code($e->getCode() ?: 500);
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
