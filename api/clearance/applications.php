<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../includes/config/database.php';
require_once '../../includes/classes/Auth.php';

// Check if user is authenticated
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Check if user has clearance management permissions
if (!$auth->hasPermission('manage_clearance_applications')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
    exit;
}

$db = Database::getInstance();
$connection = $db->getConnection();

// Handle different HTTP methods
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Get clearance applications
        handleGetApplications($connection, $auth);
        break;
        
    case 'POST':
        // Create new clearance application
        handleCreateApplication($connection, $auth);
        break;
        
    case 'PUT':
        // Update clearance application
        handleUpdateApplication($connection, $auth);
        break;
        
    case 'DELETE':
        // Delete clearance application
        handleDeleteApplication($connection, $auth);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}

// Get clearance applications
function handleGetApplications($connection, $auth) {
    try {
        $currentUser = $auth->getCurrentUser();
        $userId = $currentUser['user_id'];
        $userRole = $currentUser['role_name'];
        
        // Build query based on user role
        $whereConditions = [];
        $params = [];
        
        if ($userRole === 'Student' || $userRole === 'Faculty') {
            // Users can only see their own applications
            $whereConditions[] = "ca.user_id = ?";
            $params[] = $userId;
        }
        // Admin and School Administrator can see all applications
        
        // Apply filters
        $status = $_GET['status'] ?? null;
        $periodId = $_GET['period_id'] ?? null;
        $userType = $_GET['user_type'] ?? null;
        
        if ($status) {
            $whereConditions[] = "ca.status = ?";
            $params[] = $status;
        }
        
        if ($periodId) {
            $whereConditions[] = "ca.period_id = ?";
            $params[] = $periodId;
        }
        
        if ($userType) {
            // Filter by user type (student/faculty) based on role
            if ($userType === 'student') {
                $whereConditions[] = "u.user_id IN (SELECT ur.user_id FROM user_roles ur JOIN roles r ON ur.role_id = r.role_id WHERE r.role_name = 'Student')";
            } elseif ($userType === 'faculty') {
                $whereConditions[] = "u.user_id IN (SELECT ur.user_id FROM user_roles ur JOIN roles r ON ur.role_id = r.role_id WHERE r.role_name = 'Faculty')";
            }
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        $sql = "SELECT ca.*, u.first_name, u.last_name, u.username, u.email,
                       cp.start_date, cp.end_date, cp.is_active as period_active,
                       ay.year as academic_year, s.semester_name
                FROM clearance_applications ca
                JOIN users u ON ca.user_id = u.user_id
                JOIN clearance_periods cp ON ca.period_id = cp.period_id
                JOIN academic_years ay ON cp.academic_year_id = ay.academic_year_id
                JOIN semesters s ON cp.semester_id = s.semester_id
                $whereClause
                ORDER BY ca.applied_at DESC";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute($params);
        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'applications' => $applications,
            'total' => count($applications)
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Create new clearance application
function handleCreateApplication($connection, $auth) {
    try {
        $currentUser = $auth->getCurrentUser();
        $userId = $currentUser['user_id'];
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
            return;
        }
        
        // Validate required fields
        $requiredFields = ['period_id'];
        foreach ($requiredFields as $field) {
            if (empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
                return;
            }
        }
        
        // Check if period exists and is active
        $stmt = $connection->prepare("SELECT period_id FROM clearance_periods WHERE period_id = ? AND is_active = 1");
        $stmt->execute([$input['period_id']]);
        if (!$stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid or inactive clearance period']);
            return;
        }
        
        // Check if user already has an application for this period
        $stmt = $connection->prepare("SELECT application_id FROM clearance_applications WHERE user_id = ? AND period_id = ?");
        $stmt->execute([$userId, $input['period_id']]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Application already exists for this period']);
            return;
        }
        
        // Create application
        $sql = "INSERT INTO clearance_applications (user_id, period_id, status) VALUES (?, ?, 'pending')";
        $stmt = $connection->prepare($sql);
        $stmt->execute([$userId, $input['period_id']]);
        
        $applicationId = $connection->lastInsertId();
        
        // Create signatory status records for all requirements
        $stmt = $connection->prepare("
            INSERT INTO clearance_signatory_status (application_id, requirement_id, status)
            SELECT ?, requirement_id, 'pending'
            FROM clearance_requirements
            WHERE clearance_type = (SELECT role_name FROM roles WHERE role_id = (SELECT role_id FROM users WHERE user_id = ?))
        ");
        $stmt->execute([$applicationId, $userId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Clearance application created successfully',
            'application_id' => $applicationId
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Update clearance application
function handleUpdateApplication($connection, $auth) {
    try {
        $currentUser = $auth->getCurrentUser();
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['application_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Application ID is required']);
            return;
        }
        
        $applicationId = $input['application_id'];
        
        // Check if user has permission to update this application
        $stmt = $connection->prepare("SELECT user_id FROM clearance_applications WHERE application_id = ?");
        $stmt->execute([$applicationId]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$application) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Application not found']);
            return;
        }
        
        // Only allow users to update their own applications, or admins to update any
        if ($application['user_id'] != $currentUser['user_id'] && $currentUser['role_name'] !== 'Admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
            return;
        }
        
        // Build update fields
        $updateFields = [];
        $params = [];
        
        if (isset($input['status'])) {
            $updateFields[] = "status = ?";
            $params[] = $input['status'];
            
            if ($input['status'] === 'completed') {
                $updateFields[] = "completed_at = NOW()";
            }
        }
        
        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No fields to update']);
            return;
        }
        
        $params[] = $applicationId;
        
        $sql = "UPDATE clearance_applications SET " . implode(', ', $updateFields) . " WHERE application_id = ?";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute($params);
        
        echo json_encode([
            'success' => true,
            'message' => 'Clearance application updated successfully'
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Delete clearance application
function handleDeleteApplication($connection, $auth) {
    try {
        $currentUser = $auth->getCurrentUser();
        $applicationId = $_GET['id'] ?? null;
        
        if (!$applicationId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Application ID is required']);
            return;
        }
        
        // Check if application exists and user has permission
        $stmt = $connection->prepare("SELECT user_id FROM clearance_applications WHERE application_id = ?");
        $stmt->execute([$applicationId]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$application) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Application not found']);
            return;
        }
        
        // Only allow users to delete their own applications, or admins to delete any
        if ($application['user_id'] != $currentUser['user_id'] && $currentUser['role_name'] !== 'Admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
            return;
        }
        
        // Delete related signatory status records first
        $stmt = $connection->prepare("DELETE FROM clearance_signatory_status WHERE application_id = ?");
        $stmt->execute([$applicationId]);
        
        // Delete application
        $stmt = $connection->prepare("DELETE FROM clearance_applications WHERE application_id = ?");
        $stmt->execute([$applicationId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Clearance application deleted successfully'
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>
