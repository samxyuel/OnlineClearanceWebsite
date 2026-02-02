<?php
/**
 * Sector-Based Signatory Assignments API
 * Handles CRUD operations for sector-specific signatory assignments
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../includes/config/database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// Validate clearance type
$validClearanceTypes = ['College', 'Senior High School', 'Faculty'];

function validateClearanceType($type) {
    global $validClearanceTypes;
    return in_array($type, $validClearanceTypes);
}

function getSectorSignatories($pdo, $clearanceType = null) {
    $sql = "
        SELECT 
            ssa.assignment_id,
            ssa.clearance_type,
            ssa.user_id,
            ssa.designation_id,
            ssa.is_program_head,
            ssa.department_id,
            ssa.is_required_first,
            ssa.is_required_last,
            ssa.is_active,
            u.first_name,
            u.last_name,
            u.username as employee_number,
            d.designation_name,
            dept.department_name,
            s.sector_name
        FROM sector_signatory_assignments ssa
        LEFT JOIN users u ON ssa.user_id = u.user_id
        LEFT JOIN designations d ON ssa.designation_id = d.designation_id
        LEFT JOIN departments dept ON ssa.department_id = dept.department_id
        LEFT JOIN sectors s ON dept.sector_id = s.sector_id
        WHERE ssa.is_active = 1
    ";
    
    $params = [];
    if ($clearanceType) {
        $sql .= " AND ssa.clearance_type = ?";
        $params[] = $clearanceType;
    }
    
    $sql .= " ORDER BY ssa.is_required_first DESC, ssa.is_required_last ASC, d.designation_name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getProgramHeadsBySector($pdo, $clearanceType) {
    $sql = "
        SELECT 
            ssa.user_id,
            ssa.department_id,
            u.first_name,
            u.last_name,
            u.username as employee_number,
            dept.department_name,
            s.sector_name
        FROM sector_signatory_assignments ssa
        LEFT JOIN users u ON ssa.user_id = u.user_id
        LEFT JOIN departments dept ON ssa.department_id = dept.department_id
        LEFT JOIN sectors s ON dept.sector_id = s.sector_id
        WHERE ssa.clearance_type = ? 
        AND ssa.is_program_head = 1 
        AND ssa.is_active = 1
        ORDER BY dept.department_name
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$clearanceType]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function assignSignatory($pdo, $data) {
    $requiredFields = ['clearance_type', 'user_id', 'designation_id'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    if (!validateClearanceType($data['clearance_type'])) {
        throw new Exception("Invalid clearance type");
    }

    error_log("API: Attempting to assign signatory. Data: " . json_encode($data));
    
    // Check if assignment already exists (active or inactive)
    $checkSql = "
        SELECT assignment_id, is_active FROM sector_signatory_assignments 
        WHERE clearance_type = ? AND user_id = ? AND designation_id = ?
    ";
    $stmt = $pdo->prepare($checkSql);
    $stmt->execute([
        $data['clearance_type'],
        $data['user_id'],
        $data['designation_id']
    ]);
    $existingAssignment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingAssignment) {
        if ($existingAssignment['is_active'] == 1) {
            error_log("API: Assignment already exists and is active. No action needed.");
            // It's already active, do nothing.
            return $existingAssignment['assignment_id'];
        } else {
            // It's inactive, so reactivate it (soft-undelete)
            error_log("API: Reactivating existing inactive assignment ID: " . $existingAssignment['assignment_id']);
            $updateSql = "UPDATE sector_signatory_assignments SET is_active = 1, updated_at = NOW() WHERE assignment_id = ?";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([$existingAssignment['assignment_id']]);
            return $existingAssignment['assignment_id'];
        }
    }
    
    error_log("API: No existing assignment found. Creating a new one.");
    // If no assignment exists, insert a new one
    $sql = "
        INSERT INTO sector_signatory_assignments 
        (clearance_type, user_id, designation_id, is_program_head, department_id, is_active, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['clearance_type'],
        $data['user_id'],
        $data['designation_id'],
        $data['is_program_head'] ?? 0,
        $data['department_id'] ?? null
    ]);
    
    return $pdo->lastInsertId();
}

function removeSignatory($pdo, $data) {
    $requiredFields = ['clearance_type', 'user_id', 'designation_id'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    if (!validateClearanceType($data['clearance_type'])) {
        throw new Exception("Invalid clearance type");
    }
    
    $sql = "
        UPDATE sector_signatory_assignments 
        SET is_active = 0, updated_at = NOW()
        WHERE clearance_type = ? AND user_id = ? AND designation_id = ?
    ";
    
    $params = [$data['clearance_type'], $data['user_id'], $data['designation_id']];
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    error_log("API: Removed " . $stmt->rowCount() . " signatory assignment(s).");
    
    return $stmt->rowCount();
}

function removeProgramHeadsFromSector($pdo, $clearanceType) {
    if (!validateClearanceType($clearanceType)) {
        throw new Exception("Invalid clearance type");
    }
    
    // Get Program Head designation ID
    $stmt = $pdo->prepare("SELECT designation_id FROM designations WHERE designation_name = 'Program Head' LIMIT 1");
    $stmt->execute();
    $programHeadDesignation = $stmt->fetch();
    
    if (!$programHeadDesignation) {
        throw new Exception("Program Head designation not found");
    }
    
    $designationId = $programHeadDesignation['designation_id'];
    
    $sql = "
        UPDATE sector_signatory_assignments 
        SET is_active = 0, updated_at = NOW()
        WHERE clearance_type = ? AND designation_id = ? AND is_program_head = 1
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$clearanceType, $designationId]);
    
    return $stmt->rowCount();
}

// Route handling
switch ($method) {
    case 'GET':
         $clearanceType = $_GET['clearance_type'] ?? null;
        
        if ($clearanceType && !validateClearanceType($clearanceType)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid clearance type']);
            exit();
        }
        
        try {
            $signatories = getSectorSignatories($pdo, $clearanceType);
            echo json_encode([
                'success' => true,
                'signatories' => $signatories,
                'total' => count($signatories)
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'POST':
        if (!$input) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No input data provided']);
            exit();
        }

        try {
            if (isset($input['action'])) {
                // No POST actions are currently supported besides direct assignment.
                throw new Exception("Invalid action specified");
            } else {
                $assignmentId = assignSignatory($pdo, $input);
                echo json_encode([
                    'success' => true,
                    'message' => 'Signatory assigned successfully',
                    'assignment_id' => $assignmentId
                ]);
            }
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        if (!$input) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No input data provided']);
            exit();
        }

        // Check for bulk delete action
        if (isset($input['action']) && $input['action'] === 'bulk_delete') {
            handleBulkRemove($pdo, $input);
            exit();
        }

        try {
            $removedCount = removeSignatory($pdo, $input);
            echo json_encode([
                'success' => true,
                'message' => 'Signatory removed successfully',
                'removed_count' => $removedCount
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}

function handleBulkRemove($pdo, $data)
{
    if (empty($data['clearance_type'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'clearance_type is required for bulk delete']);
        exit();
    }

    if (!validateClearanceType($data['clearance_type']))
    {
        throw new Exception("Invalid clearance type");
    }

    error_log("API: Bulk removing all signatories for clearance_type: " . $data['clearance_type']);    
    $sql = "
        UPDATE sector_signatory_assignments 
        SET is_active = 0, updated_at = NOW()
        WHERE clearance_type = ? AND is_active = 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$data['clearance_type']]);
    $removedCount = $stmt->rowCount();
    error_log("API: Bulk removed " . $removedCount . " assignments.");    

    echo json_encode([
        'success' => true,
        'message' => "Bulk removal successful for {$data['clearance_type']}",
        'removed_count' => $removedCount
    ]);
}
?>
