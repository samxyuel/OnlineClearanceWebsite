<?php
/**
 * Clearance Forms API
 * Handles clearance form management and operations
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
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

$db = Database::getInstance();
$connection = $db->getConnection();

// Handle different HTTP methods
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        handleGetForms($connection);
        break;
        
    case 'POST':
        handleCreateForm($connection);
        break;
        
    case 'PUT':
        handleUpdateForm($connection);
        break;
        
    case 'DELETE':
        handleDeleteForm($connection);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}

// Get clearance forms
function handleGetForms($connection) {
    try {
        $userId = $_GET['user_id'] ?? null;
        $sector = $_GET['sector'] ?? null;
        $status = $_GET['status'] ?? null;
        $academicYearId = $_GET['academic_year_id'] ?? null;
        $semesterId = $_GET['semester_id'] ?? null;
        
        // Build query
        $sql = "SELECT 
                    cf.clearance_form_id,
                    cf.user_id,
                    cf.academic_year_id,
                    cf.semester_id,
                    cf.clearance_type,
                    cf.clearance_form_progress,
                    cf.applied_at,
                    cf.completed_at,
                    cf.rejected_at,
                    cf.grace_period_ends,
                    cf.created_at,
                    cf.updated_at,
                    ay.year as academic_year,
                    s.semester_name,
                    u.first_name,
                    u.last_name,
                    u.middle_name,
                    u.username,
                    CASE 
                        WHEN cf.clearance_type = 'College' THEN st.student_id
                        WHEN cf.clearance_type = 'Senior High School' THEN st.student_id
                        WHEN cf.clearance_type = 'Faculty' THEN f.employee_number
                        ELSE NULL
                    END as identifier
                FROM clearance_forms cf
                JOIN academic_years ay ON cf.academic_year_id = ay.academic_year_id
                JOIN semesters s ON cf.semester_id = s.semester_id
                JOIN users u ON cf.user_id = u.user_id
                LEFT JOIN students st ON cf.user_id = st.user_id AND cf.clearance_type IN ('College', 'Senior High School')
                LEFT JOIN faculty f ON cf.user_id = f.user_id AND cf.clearance_type = 'Faculty'
                WHERE 1=1";
        
        $params = [];
        
        if ($userId) {
            $sql .= " AND cf.user_id = ?";
            $params[] = $userId;
        }
        
        if ($sector) {
            $sql .= " AND cf.clearance_type = ?";
            $params[] = $sector;
        }
        
        if ($status) {
            $sql .= " AND cf.clearance_form_progress = ?";
            $params[] = $status;
        }
        
        if ($academicYearId) {
            $sql .= " AND cf.academic_year_id = ?";
            $params[] = $academicYearId;
        }
        
        if ($semesterId) {
            $sql .= " AND cf.semester_id = ?";
            $params[] = $semesterId;
        }
        
        $sql .= " ORDER BY cf.created_at DESC";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute($params);
        $forms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get signatory actions for each form
        foreach ($forms as &$form) {
            $form['signatory_actions'] = getSignatoryActions($connection, $form['clearance_form_id']);
        }
        
        echo json_encode([
            'success' => true,
            'forms' => $forms,
            'total' => count($forms)
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Create clearance form
function handleCreateForm($connection) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
            return;
        }
        
        // Validate required fields
        $requiredFields = ['user_id', 'academic_year_id', 'semester_id', 'clearance_type'];
        foreach ($requiredFields as $field) {
            if (empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
                return;
            }
        }
        
        // Validate clearance type
        $validTypes = ['College', 'Senior High School', 'Faculty'];
        if (!in_array($input['clearance_type'], $validTypes)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid clearance type. Must be one of: ' . implode(', ', $validTypes)]);
            return;
        }
        
        // Check if form already exists
        $stmt = $connection->prepare("
            SELECT clearance_form_id FROM clearance_forms 
            WHERE user_id = ? AND academic_year_id = ? AND semester_id = ? AND clearance_type = ?
        ");
        $stmt->execute([$input['user_id'], $input['academic_year_id'], $input['semester_id'], $input['clearance_type']]);
        
        if ($stmt->rowCount() > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Clearance form already exists for this user and period.']);
            return;
        }
        
        // Generate clearance form ID
        $formId = generateClearanceFormId($connection);
        
        // Insert new form
        $sql = "INSERT INTO clearance_forms (
                    clearance_form_id,
                    user_id,
                    academic_year_id,
                    semester_id,
                    clearance_type,
                    status,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, 'Unapplied', NOW())";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute([
            $formId,
            $input['user_id'],
            $input['academic_year_id'],
            $input['semester_id'],
            $input['clearance_type']
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Clearance form created successfully',
            'clearance_form_id' => $formId
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Update clearance form
function handleUpdateForm($connection) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || empty($input['clearance_form_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Clearance form ID is required']);
            return;
        }
        
        $formId = $input['clearance_form_id'];
        
        // Check if form exists
        $stmt = $connection->prepare("SELECT * FROM clearance_forms WHERE clearance_form_id = ?");
        $stmt->execute([$formId]);
        $existingForm = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$existingForm) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Clearance form not found']);
            return;
        }
        
        // Handle specific actions
        if (isset($input['action'])) {
            $action = $input['action'];
            
            if ($action === 'apply') {
                return applyForClearance($connection, $formId, $existingForm);
            } elseif ($action === 'withdraw') {
                return withdrawClearanceApplication($connection, $formId);
            }
        }
        
        // Update fields
        $updateFields = [];
        $params = [];
        
        $allowedFields = ['status', 'applied_at', 'completed_at', 'rejected_at', 'grace_period_ends'];
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateFields[] = "$field = ?";
                $params[] = $input[$field];
            }
        }
        
        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No fields to update']);
            return;
        }
        
        $updateFields[] = "updated_at = NOW()";
        $params[] = $formId;
        
        $sql = "UPDATE clearance_forms SET " . implode(', ', $updateFields) . " WHERE clearance_form_id = ?";
        $stmt = $connection->prepare($sql);
        $stmt->execute($params);
        
        echo json_encode([
            'success' => true,
            'message' => 'Clearance form updated successfully'
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Apply for clearance
function applyForClearance($connection, $formId, $form) {
    try {
        // Check if there's an active clearance period for this sector
        $stmt = $connection->prepare("
            SELECT period_id FROM clearance_periods 
            WHERE academic_year_id = ? AND semester_id = ? AND sector = ? AND status = 'Ongoing'
        ");
        $stmt->execute([$form['academic_year_id'], $form['semester_id'], $form['clearance_type']]);
        $activePeriod = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$activePeriod) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No active clearance period for this sector.']);
            return;
        }
        
        // Update form status
        $stmt = $connection->prepare("
            UPDATE clearance_forms 
            SET status = 'Pending', applied_at = NOW(), updated_at = NOW() 
            WHERE clearance_form_id = ?
        ");
        $stmt->execute([$formId]);
        
        // Create signatory actions for this form
        createSignatoryActionsForForm($connection, $formId, $activePeriod['period_id']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Clearance application submitted successfully'
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Withdraw clearance application
function withdrawClearanceApplication($connection, $formId) {
    try {
        $stmt = $connection->prepare("
            UPDATE clearance_forms 
            SET status = 'Unapplied', applied_at = NULL, updated_at = NOW() 
            WHERE clearance_form_id = ?
        ");
        $stmt->execute([$formId]);
        
        // Delete signatory actions
        $stmt = $connection->prepare("DELETE FROM clearance_signatory_actions WHERE clearance_form_id = ?");
        $stmt->execute([$formId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Clearance application withdrawn successfully'
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Delete clearance form
function handleDeleteForm($connection) {
    try {
        $formId = $_GET['clearance_form_id'] ?? null;
        
        if (!$formId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Clearance form ID is required']);
            return;
        }
        
        // Check if form exists
        $stmt = $connection->prepare("SELECT status FROM clearance_forms WHERE clearance_form_id = ?");
        $stmt->execute([$formId]);
        $form = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$form) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Clearance form not found']);
            return;
        }
        
        // Only allow deletion of unapplied forms
        if ($form['status'] !== 'Unapplied') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Cannot delete applied clearance forms']);
            return;
        }
        
        // Delete signatory actions first
        $stmt = $connection->prepare("DELETE FROM clearance_signatory_actions WHERE clearance_form_id = ?");
        $stmt->execute([$formId]);
        
        // Delete form
        $stmt = $connection->prepare("DELETE FROM clearance_forms WHERE clearance_form_id = ?");
        $stmt->execute([$formId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Clearance form deleted successfully'
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Helper function to generate clearance form ID
function generateClearanceFormId($connection) {
    $year = date('Y');
    $stmt = $connection->query("SELECT COUNT(*) + 1 as next_id FROM clearance_forms WHERE clearance_form_id LIKE 'CF-$year-%'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $nextId = str_pad($result['next_id'], 5, '0', STR_PAD_LEFT);
    return "CF-$year-$nextId";
}

// Helper function to get signatory actions for a form
function getSignatoryActions($connection, $formId) {
    try {
        $stmt = $connection->prepare("
            SELECT 
                csa.action_id,
                csa.action,
                csa.remarks,
                csa.rejection_reason_id,
                csa.additional_remarks,
                csa.date_signed,
                csa.grace_period_ends,
                csa.is_undone,
                csn.staff_id,
                d.designation_name,
                u.first_name,
                u.last_name
            FROM clearance_signatory_actions csa
            JOIN clearance_signatories_new csn ON csa.signatory_id = csn.signatory_id
            JOIN designations d ON csn.designation_id = d.designation_id
            JOIN staff s ON csn.staff_id = s.employee_number
            JOIN users u ON s.user_id = u.user_id
            WHERE csa.clearance_form_id = ?
            ORDER BY csa.created_at ASC
        ");
        $stmt->execute([$formId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting signatory actions: " . $e->getMessage());
        return [];
    }
}

// Helper function to create signatory actions for a form
function createSignatoryActionsForForm($connection, $formId, $periodId) {
    try {
        // Get signatories for this period
        $stmt = $connection->prepare("
            SELECT signatory_id FROM clearance_signatories_new 
            WHERE clearance_period_id = ? AND is_active = 1
        ");
        $stmt->execute([$periodId]);
        $signatories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Create actions for each signatory
        foreach ($signatories as $signatory) {
            $stmt = $connection->prepare("
                INSERT INTO clearance_signatory_actions (
                    clearance_form_id,
                    signatory_id,
                    action,
                    created_at
                ) VALUES (?, ?, 'Pending', NOW())
            ");
            $stmt->execute([$formId, $signatory['signatory_id']]);
        }
        
    } catch (Exception $e) {
        error_log("Error creating signatory actions: " . $e->getMessage());
        throw $e;
    }
}
?>
