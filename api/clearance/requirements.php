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
if (!$auth->hasPermission('manage_clearance_requirements')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
    exit;
}

$db = Database::getInstance();
$connection = $db->getConnection();

// Handle different HTTP methods
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Get clearance requirements
        handleGetRequirements($connection);
        break;
        
    case 'POST':
        // Create new clearance requirement
        handleCreateRequirement($connection);
        break;
        
    case 'PUT':
        // Update clearance requirement
        handleUpdateRequirement($connection);
        break;
        
    case 'DELETE':
        // Delete clearance requirement
        handleDeleteRequirement($connection);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}

// Get clearance requirements
function handleGetRequirements($connection) {
    try {
        $clearanceType = $_GET['clearance_type'] ?? null;
        $isRequired = $_GET['is_required'] ?? null;
        
        $whereConditions = [];
        $params = [];
        
        if ($clearanceType) {
            $whereConditions[] = "cr.clearance_type = ?";
            $params[] = $clearanceType;
        }
        
        if ($isRequired !== null) {
            $whereConditions[] = "cr.is_required = ?";
            $params[] = $isRequired ? 1 : 0;
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        $sql = "SELECT cr.*, d.designation_name, d.description as designation_description
                FROM clearance_requirements cr
                JOIN designations d ON cr.designation_id = d.designation_id
                $whereClause
                ORDER BY cr.clearance_type, cr.requirement_id";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute($params);
        $requirements = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'requirements' => $requirements,
            'total' => count($requirements)
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Create new clearance requirement
function handleCreateRequirement($connection) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
            return;
        }
        
        // Validate required fields
        $requiredFields = ['clearance_type', 'designation_id'];
        foreach ($requiredFields as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
                return;
            }
        }
        
        $sql = "INSERT INTO clearance_requirements 
                (clearance_type, designation_id, is_required, is_department_specific, applies_to_departments) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute([
            $input['clearance_type'],
            $input['designation_id'],
            $input['is_required'] ?? true,
            $input['is_department_specific'] ?? false,
            $input['applies_to_departments'] ?? null
        ]);
        
        $requirementId = $connection->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Clearance requirement created successfully',
            'requirement_id' => $requirementId
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Update clearance requirement
function handleUpdateRequirement($connection) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['requirement_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Requirement ID is required']);
            return;
        }
        
        $requirementId = $input['requirement_id'];
        
        // Build update fields
        $updateFields = [];
        $params = [];
        
        if (isset($input['clearance_type'])) {
            $updateFields[] = "clearance_type = ?";
            $params[] = $input['clearance_type'];
        }
        
        if (isset($input['designation_id'])) {
            $updateFields[] = "designation_id = ?";
            $params[] = $input['designation_id'];
        }
        
        if (isset($input['is_required'])) {
            $updateFields[] = "is_required = ?";
            $params[] = $input['is_required'] ? 1 : 0;
        }
        
        if (isset($input['is_department_specific'])) {
            $updateFields[] = "is_department_specific = ?";
            $params[] = $input['is_department_specific'] ? 1 : 0;
        }
        
        if (isset($input['applies_to_departments'])) {
            $updateFields[] = "applies_to_departments = ?";
            $params[] = $input['applies_to_departments'];
        }
        
        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No fields to update']);
            return;
        }
        
        $params[] = $requirementId;
        
        $sql = "UPDATE clearance_requirements SET " . implode(', ', $updateFields) . " WHERE requirement_id = ?";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute($params);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Clearance requirement updated successfully'
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Requirement not found']);
        }
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Delete clearance requirement
function handleDeleteRequirement($connection) {
    try {
        $requirementId = $_GET['id'] ?? null;
        
        if (!$requirementId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Requirement ID is required']);
            return;
        }
        
        $sql = "DELETE FROM clearance_requirements WHERE requirement_id = ?";
        $stmt = $connection->prepare($sql);
        $stmt->execute([$requirementId]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Clearance requirement deleted successfully'
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Requirement not found']);
        }
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>
