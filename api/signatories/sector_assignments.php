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
    
    // Check if assignment already exists
    $checkSql = "
        SELECT assignment_id FROM sector_signatory_assignments 
        WHERE clearance_type = ? AND user_id = ? AND designation_id = ?
        AND (department_id = ? OR (department_id IS NULL AND ? IS NULL))
    ";
    $stmt = $pdo->prepare($checkSql);
    $stmt->execute([
        $data['clearance_type'],
        $data['user_id'],
        $data['designation_id'],
        $data['department_id'] ?? null,
        $data['department_id'] ?? null
    ]);
    
    if ($stmt->fetch()) {
        throw new Exception("Assignment already exists");
    }
    
    $sql = "
        INSERT INTO sector_signatory_assignments 
        (clearance_type, user_id, designation_id, is_program_head, department_id, is_required_first, is_required_last, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, 1)
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['clearance_type'],
        $data['user_id'],
        $data['designation_id'],
        $data['is_program_head'] ?? 0,
        $data['department_id'] ?? null,
        $data['is_required_first'] ?? 0,
        $data['is_required_last'] ?? 0
    ]);
    
    return $pdo->lastInsertId();
}

function removeSignatory($pdo, $data) {
    $requiredFields = ['clearance_type', 'user_id'];
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
        WHERE clearance_type = ? AND user_id = ?
    ";
    
    if (isset($data['designation_id'])) {
        $sql .= " AND designation_id = ?";
        $params = [$data['clearance_type'], $data['user_id'], $data['designation_id']];
    } else {
        $params = [$data['clearance_type'], $data['user_id']];
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->rowCount();
}

function assignProgramHeadsToSector($pdo, $clearanceType) {
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
    
    // Get all Program Heads assigned to departments in this sector
    $sql = "
        SELECT DISTINCT sda.staff_id, sda.department_id
        FROM staff_department_assignments sda
        JOIN departments d ON sda.department_id = d.department_id
        JOIN sectors s ON d.sector_id = s.sector_id
        WHERE s.sector_name = ? AND sda.is_primary = 1
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$clearanceType]);
    $programHeads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $assignedCount = 0;
    foreach ($programHeads as $ph) {
        try {
            // Check if already assigned
            $checkSql = "
                SELECT assignment_id FROM sector_signatory_assignments 
                WHERE clearance_type = ? AND user_id = ? AND designation_id = ? AND department_id = ?
            ";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([$clearanceType, $ph['staff_id'], $designationId, $ph['department_id']]);
            
            if (!$checkStmt->fetch()) {
                // Insert new assignment
                $insertSql = "
                    INSERT INTO sector_signatory_assignments 
                    (clearance_type, user_id, designation_id, is_program_head, department_id, is_active)
                    VALUES (?, ?, ?, 1, ?, 1)
                ";
                $insertStmt = $pdo->prepare($insertSql);
                $insertStmt->execute([$clearanceType, $ph['staff_id'], $designationId, $ph['department_id']]);
                $assignedCount++;
            }
        } catch (Exception $e) {
            // Continue with other assignments
            continue;
        }
    }
    
    return $assignedCount;
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
                switch ($input['action']) {
                    case 'assign_program_heads':
                        $count = assignProgramHeadsToSector($pdo, $input['clearance_type']);
                        echo json_encode([
                            'success' => true,
                            'message' => "Assigned $count Program Heads to {$input['clearance_type']} clearance",
                            'assigned_count' => $count
                        ]);
                        break;
                        
                    case 'remove_program_heads':
                        $count = removeProgramHeadsFromSector($pdo, $input['clearance_type']);
                        echo json_encode([
                            'success' => true,
                            'message' => "Removed $count Program Heads from {$input['clearance_type']} clearance",
                            'removed_count' => $count
                        ]);
                        break;
                        
                    default:
                        $assignmentId = assignSignatory($pdo, $input);
                        echo json_encode([
                            'success' => true,
                            'message' => 'Signatory assigned successfully',
                            'assignment_id' => $assignmentId
                        ]);
                }
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
?>
