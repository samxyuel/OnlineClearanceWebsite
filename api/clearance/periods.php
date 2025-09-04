<?php
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

// // Check if user has clearance management permissions
// if (!$auth->hasPermission('manage_clearance_periods')) {
//     http_response_code(403);
//     echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
//     exit;
// }

$db = Database::getInstance();
$connection = $db->getConnection();

// Handle different HTTP methods
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Get clearance periods
        handleGetPeriods($connection);
        break;
        
    case 'POST':
        // Create new clearance period
        handleCreatePeriod($connection);
        break;
        
    case 'PUT':
        // Update clearance period
        handleUpdatePeriod($connection);
        break;
        
    case 'DELETE':
        // Delete clearance period
        handleDeletePeriod($connection);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}

// Get clearance periods
function handleGetPeriods($connection) {
    try {
        // Fetch active period (for banner)
        $activeSql = "SELECT 
                          cp.period_id,
                          ay.year AS school_year,
                          s.semester_name,
                          cp.status,
                          cp.is_active,
                          cp.start_date,
                          cp.end_date,
                          cp.ended_at
                      FROM clearance_periods cp
                      JOIN academic_years ay ON cp.academic_year_id = ay.academic_year_id
                      JOIN semesters s ON cp.semester_id = s.semester_id
                      WHERE cp.is_active = 1
                      LIMIT 1";
        $activeStmt = $connection->query($activeSql);
        $active = $activeStmt->fetch(PDO::FETCH_ASSOC) ?: null;

        // Fetch all periods list
        $sql = "SELECT cp.*, ay.year as academic_year, s.semester_name 
                FROM clearance_periods cp
                JOIN academic_years ay ON cp.academic_year_id = ay.academic_year_id
                JOIN semesters s ON cp.semester_id = s.semester_id
                ORDER BY cp.created_at DESC";
        $stmt = $connection->query($sql);
        $periods = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'active_period' => $active,
            'periods' => $periods,
            'total' => count($periods)
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Create new clearance period
function handleCreatePeriod($connection) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
            return;
        }
        
        // Validate required fields (end_date optional – will be set to start_date if omitted)
        $requiredFields = ['academic_year_id', 'semester_id', 'start_date'];
        foreach ($requiredFields as $field) {
            if (empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
                return;
            }
        }
        
        // Check if there's already an active period for this academic year
        $stmt = $connection->prepare("SELECT COUNT(*) FROM clearance_periods WHERE is_active = 1 AND academic_year_id = ?");
        $stmt->execute([$input['academic_year_id']]);
        $activeCount = $stmt->fetchColumn();
        
        if ($activeCount > 0 && ($input['is_active'] ?? false)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Another clearance period in this school year is already active. Deactivate it first.']);
            return;
        }
        
        // Validate dates
        $startDate = new DateTime($input['start_date']);
        $endDate = isset($input['end_date']) && $input['end_date'] !== null && $input['end_date'] !== ''
            ? new DateTime($input['end_date'])
            : clone $startDate; // placeholder until term is ended
        
        if ($startDate > $endDate) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'End date must be after start date']);
            return;
        }
        
        // Determine status based on requested is_active
        $newStatus = ($input['is_active'] ?? false) ? 'active' : 'inactive';

        // Insert new period (include status)
        $sql = "INSERT INTO clearance_periods (academic_year_id, semester_id, start_date, end_date, is_active, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute([
            $input['academic_year_id'],
            $input['semester_id'],
            $input['start_date'],
            $endDate->format('Y-m-d'),
            $input['is_active'] ?? false,
            $newStatus
        ]);
        
        $periodId = $connection->lastInsertId();
        
        // If this period is active, deactivate other periods in the same academic year
        if ($input['is_active'] ?? false) {
            $stmt = $connection->prepare("UPDATE clearance_periods 
                                          SET is_active = 0, status = 'deactivated' 
                                          WHERE period_id != ? AND academic_year_id = ?");
            $stmt->execute([$periodId, $input['academic_year_id']]);
        }
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Clearance period created successfully',
            'period_id' => $periodId
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Update clearance period
function handleUpdatePeriod($connection) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || empty($input['period_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Period ID is required']);
            return;
        }
        
        $periodId = (int)$input['period_id'];
        
        // Check if period exists
        $stmt = $connection->prepare("SELECT * FROM clearance_periods WHERE period_id = ?");
        $stmt->execute([$periodId]);
        $existingPeriod = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$existingPeriod) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Clearance period not found']);
            return;
        }
        
        // Lifecycle-style actions shortcut
        if (isset($input['action'])) {
            $action = $input['action'];
            if ($action === 'activate') {
                // Allow reactivation of the SAME term when it is currently deactivated,
                // but still block if another term is active. When activating a DIFFERENT term,
                // require that all other terms are ended (no active or deactivated terms).
                if (strtolower($existingPeriod['status']) === 'deactivated') {
                    // Reactivating same term → only ensure no other term is active
                    $chk = $connection->prepare("SELECT COUNT(*) FROM clearance_periods WHERE academic_year_id = ? AND period_id != ? AND status = 'active'");
                    $chk->execute([$existingPeriod['academic_year_id'], $periodId]);
                    if ((int)$chk->fetchColumn() > 0) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Another term is currently active. End or deactivate it first.']);
                        return;
                    }
                } else {
                    // Activating a different term → no other active or deactivated terms allowed
                    $chk = $connection->prepare("SELECT COUNT(*) FROM clearance_periods WHERE academic_year_id = ? AND period_id != ? AND status IN ('active','deactivated')");
                    $chk->execute([$existingPeriod['academic_year_id'], $periodId]);
                    if ((int)$chk->fetchColumn() > 0) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Another term in this school year is not ended. End it first before activating a different term.']);
                        return;
                    }
                }
                // Activate this period; deactivate others in same academic year
                // Ensure start_date exists
                if (empty($existingPeriod['start_date'])) {
                    $stmt = $connection->prepare("UPDATE clearance_periods SET start_date = CURDATE() WHERE period_id = ?");
                    $stmt->execute([$periodId]);
                }
                // Activate
                $stmt = $connection->prepare("UPDATE clearance_periods SET is_active = 1, status = 'active', ended_at = NULL WHERE period_id = ?");
                $stmt->execute([$periodId]);
                // Deactivate others in same academic year
                $stmt = $connection->prepare("UPDATE clearance_periods SET is_active = 0, status = 'deactivated' WHERE period_id != ? AND academic_year_id = ?");
                $stmt->execute([$periodId, $existingPeriod['academic_year_id']]);

                // NEW: Reset all clearance forms for new term
                resetClearanceFormsForNewTerm($connection, $existingPeriod['academic_year_id'], $existingPeriod['semester_id']);

                echo json_encode(['success' => true, 'message' => 'Period activated and clearance forms reset']);
                return;
            } elseif ($action === 'deactivate') {
                $stmt = $connection->prepare("UPDATE clearance_periods SET is_active = 0, status = 'deactivated' WHERE period_id = ?");
                $stmt->execute([$periodId]);
                echo json_encode(['success' => true, 'message' => 'Period deactivated']);
                return;
            } elseif ($action === 'end') {
                $stmt = $connection->prepare("UPDATE clearance_periods SET is_active = 0, status = 'ended', ended_at = NOW(), end_date = CURDATE() WHERE period_id = ?");
                $stmt->execute([$periodId]);
                echo json_encode(['success' => true, 'message' => 'Period ended']);
                return;
            }
        }

        // If making this period active by field update, deactivate others in same academic year
        if (isset($input['is_active']) && $input['is_active'] && !$existingPeriod['is_active']) {
            $stmt = $connection->prepare("UPDATE clearance_periods SET is_active = 0, status = 'deactivated' WHERE period_id != ? AND academic_year_id = ?");
            $stmt->execute([$periodId, $existingPeriod['academic_year_id']]);
            // Also set status active on this period if caller forgot to include it
            $input['status'] = 'active';
        }
        
        // Build update query
        $updateFields = [];
        $params = [];
        
        $allowedFields = ['academic_year_id', 'semester_id', 'start_date', 'end_date', 'is_active', 'status'];
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
        $params[] = $periodId;
        
        $sql = "UPDATE clearance_periods SET " . implode(', ', $updateFields) . " WHERE period_id = ?";
        $stmt = $connection->prepare($sql);
        $stmt->execute($params);
        
        echo json_encode([
            'success' => true,
            'message' => 'Clearance period updated successfully'
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Delete clearance period
function handleDeletePeriod($connection) {
    try {
        $periodId = null;
        
        // Try to get from query string first
        if (isset($_GET['period_id'])) {
            $periodId = (int)$_GET['period_id'];
        } else {
            // Try to get from request body
            $input = json_decode(file_get_contents('php://input'), true);
            if ($input && isset($input['period_id'])) {
                $periodId = (int)$input['period_id'];
            }
        }
        
        if (!$periodId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Period ID is required']);
            return;
        }
        
        // Check if period exists and is not active
        $stmt = $connection->prepare("SELECT is_active FROM clearance_periods WHERE period_id = ?");
        $stmt->execute([$periodId]);
        $period = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$period) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Clearance period not found']);
            return;
        }
        
        if ($period['is_active']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Cannot delete active clearance period. Deactivate it first.']);
            return;
        }
        
        // Check if period has any applications
        $stmt = $connection->prepare("SELECT COUNT(*) FROM clearance_applications WHERE period_id = ?");
        $stmt->execute([$periodId]);
        $applicationCount = $stmt->fetchColumn();
        
        if ($applicationCount > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Cannot delete period with existing applications']);
            return;
        }
        
        // Delete period
        $stmt = $connection->prepare("DELETE FROM clearance_periods WHERE period_id = ?");
        $stmt->execute([$periodId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Clearance period deleted successfully'
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Helper function to reset all clearance forms for new term
function resetClearanceFormsForNewTerm($connection, $academicYearId, $semesterId) {
    try {
        // 1. Delete all existing clearance forms for this period
        $deleteFormsStmt = $connection->prepare("DELETE FROM clearance_forms WHERE academic_year_id = ? AND semester_id = ?");
        $deleteFormsStmt->execute([$academicYearId, $semesterId]);
        
        // 2. Get all active users (faculty and students)
        $usersStmt = $connection->prepare("
            SELECT u.user_id, r.role_name 
            FROM users u 
            JOIN roles r ON u.role_id = r.role_id 
            WHERE u.status = 'active'
        ");
        $usersStmt->execute();
        $users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 3. Create fresh clearance forms for all active users
        $insertFormStmt = $connection->prepare("
            INSERT INTO clearance_forms (user_id, academic_year_id, semester_id, clearance_type, status, created_at, updated_at) 
            VALUES (?, ?, ?, ?, 'Unapplied', NOW(), NOW())
        ");
        
        $insertSignatoryStmt = $connection->prepare("
            INSERT INTO clearance_signatories (clearance_form_id, designation_id, action, created_at, updated_at) 
            VALUES (?, ?, 'Unapplied', NOW(), NOW())
        ");
        
        foreach ($users as $user) {
            $clearanceType = ($user['role_name'] === 'Faculty') ? 'Faculty' : 'Student';
            
            // Insert clearance form
            $insertFormStmt->execute([$user['user_id'], $academicYearId, $semesterId, $clearanceType]);
            $formId = $connection->lastInsertId();
            
            // Get assigned signatories for this clearance type
            $signatoriesStmt = $connection->prepare("
                SELECT DISTINCT sa.designation_id 
                FROM signatory_assignments sa
                JOIN designations d ON d.designation_id = sa.designation_id
                WHERE sa.clearance_type = ? 
                AND sa.is_active = 1 
                AND d.is_active = 1
            ");
            $signatoriesStmt->execute([$clearanceType]);
            $signatories = $signatoriesStmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Create signatory entries for all assigned designations
            foreach ($signatories as $designationId) {
                $insertSignatoryStmt->execute([$formId, $designationId]);
            }
        }
        
    } catch (Exception $e) {
        // Log error but don't fail the activation
        error_log("Error resetting clearance forms for new term: " . $e->getMessage());
    }
}
?>
