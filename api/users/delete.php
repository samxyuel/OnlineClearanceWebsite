<?php
/**
 * Unified User Deletion API Endpoint
 * 
 * Handles deletion for all user types: students, faculty, staff
 * Supports both single and bulk deletion (for students)
 * 
 * Request Methods: POST, DELETE
 * 
 * Request Body (JSON):
 * {
 *     "user_type": "student" | "faculty" | "staff",  // Required
 *     "user_id": 123,                                 // For single deletion
 *     "user_ids": [1, 2, 3],                          // For bulk deletion (students only)
 *     "employee_id": "EMP001"                         // For faculty/staff deletion
 * }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../includes/classes/UserManager.php';
require_once '../../includes/classes/Auth.php';

// Check if user is authenticated
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Check permission
// Allow Admin with delete_users permission OR Program Head (with department restrictions)
$hasPermission = $auth->hasPermission('delete_users') || $auth->getRoleName() === 'Program Head';
if (!$hasPermission) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
    exit;
}

// Accept POST or DELETE methods
if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'DELETE'])) {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST or DELETE.']);
    exit;
}

// Parse request body
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON payload']);
    exit;
}

// Get user type (default to 'user' for backwards compatibility)
$userType = $input['user_type'] ?? 'user';

$userManager = new UserManager();
$result = ['success' => false, 'message' => 'Unknown error'];

try {
    $roleName = $auth->getRoleName();
    $currentUserId = $auth->getUserId();
    
    // For Program Heads, verify they can only delete users in their departments
    if ($roleName === 'Program Head' && $roleName !== 'Admin') {
        require_once '../../includes/helpers/department_helpers.php';
        require_once '../../includes/config/database.php';
        $pdo = Database::getInstance()->getConnection();
        $programHeadDepartments = getCrossSectorDepartmentIds($pdo, $currentUserId);
        
        if (empty($programHeadDepartments)) {
            http_response_code(403);
            echo json_encode([
                'success' => false, 
                'message' => 'You are not assigned to any departments'
            ]);
            exit;
        }
        
        // Get user IDs to check (single or bulk)
        $userIdsToCheck = [];
        if (isset($input['user_ids']) && is_array($input['user_ids'])) {
            $userIdsToCheck = $input['user_ids'];
        } elseif (isset($input['user_id'])) {
            $userIdsToCheck = [(int)$input['user_id']];
        }
        
        if (!empty($userIdsToCheck)) {
            // Prevent Program Heads from deleting staff
            if ($userType === 'staff') {
                http_response_code(403);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Program Heads cannot delete staff members'
                ]);
                exit;
            }
            
            // Verify users belong to Program Head's departments
            $placeholders = implode(',', array_fill(0, count($userIdsToCheck), '?'));
            $deptPlaceholders = implode(',', array_fill(0, count($programHeadDepartments), '?'));
            
            // Check for students
            if ($userType === 'student') {
                $checkStmt = $pdo->prepare("
                    SELECT COUNT(*) 
                    FROM students s
                    WHERE s.user_id IN ($placeholders)
                    AND s.department_id IN ($deptPlaceholders)
                ");
                $checkStmt->execute(array_merge($userIdsToCheck, $programHeadDepartments));
                $validCount = $checkStmt->fetchColumn();
                
                if ($validCount != count($userIdsToCheck)) {
                    http_response_code(403);
                    echo json_encode([
                        'success' => false, 
                        'message' => 'You can only delete students in your assigned departments'
                    ]);
                    exit;
                }
            }
            // Check for faculty
            elseif ($userType === 'faculty') {
                $checkStmt = $pdo->prepare("
                    SELECT COUNT(DISTINCT f.user_id) 
                    FROM faculty f
                    JOIN user_department_assignments uda ON f.user_id = uda.user_id
                    WHERE f.user_id IN ($placeholders)
                    AND uda.department_id IN ($deptPlaceholders)
                    AND uda.is_active = 1
                ");
                $checkStmt->execute(array_merge($userIdsToCheck, $programHeadDepartments));
                $validCount = $checkStmt->fetchColumn();
                
                if ($validCount != count($userIdsToCheck)) {
                    http_response_code(403);
                    echo json_encode([
                        'success' => false, 
                        'message' => 'You can only delete faculty in your assigned departments'
                    ]);
                    exit;
                }
            }
        }
    }
    
    switch ($userType) {
        case 'student':
            // Handle student deletion (single or bulk)
            if (isset($input['user_ids']) && is_array($input['user_ids'])) {
                // Bulk deletion
                $result = $userManager->deleteStudents($input['user_ids']);
            } elseif (isset($input['user_id'])) {
                // Single deletion
                $result = $userManager->deleteStudent((int)$input['user_id']);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'user_id or user_ids required for student deletion']);
                exit;
            }
            break;

        case 'faculty':
            // Handle faculty deletion (uses user_id like students)
            if (isset($input['user_id'])) {
                $result = $userManager->deleteFaculty((int)$input['user_id']);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'user_id required for faculty deletion']);
                exit;
            }
            break;

        case 'staff':
            // Handle staff deletion (uses user_id like students and faculty)
            if (isset($input['user_id'])) {
                $result = $userManager->deleteStaff((int)$input['user_id']);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'user_id required for staff deletion']);
                exit;
            }
            break;

        case 'user':
        default:
            // Backwards compatibility: generic user deletion
            $userId = null;
            
            if (isset($_GET['user_id'])) {
                $userId = (int)$_GET['user_id'];
            } elseif (isset($input['user_id'])) {
                $userId = (int)$input['user_id'];
            }
            
            if (!$userId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'user_id required']);
                exit;
            }
            
            $result = $userManager->deleteUser($userId);
            break;
    }

    // Return result
    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
