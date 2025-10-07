<?php
/**
 * Sector-Based Clearance Application API
 * Handles clearance applications with sector support
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once '../../includes/config/database.php';
require_once '../../includes/classes/Auth.php';
require_once '../../includes/functions/audit_functions.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

$userId = $auth->getUserId();

try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    // Determine user's sector
    $userSector = getUserSector($connection, $userId);
    if (!$userSector) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User sector could not be determined']);
        exit;
    }
    
    // Check if there's an active clearance period for this sector
    $stmt = $connection->prepare("
        SELECT 
            cp.period_id,
            cp.academic_year_id,
            cp.semester_id,
            cp.sector,
            cp.status,
            cp.start_date,
            cp.end_date
        FROM clearance_periods cp
        WHERE cp.sector = ? AND cp.status = 'Ongoing'
        ORDER BY cp.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$userSector]);
    $activePeriod = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$activePeriod) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "No active clearance period for {$userSector}"]);
        exit;
    }
    
    // Check if user is eligible
    $stmt = $connection->prepare("
        SELECT status, can_apply 
        FROM users 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || strtolower($user['status']) !== 'active') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Account is inactive']);
        exit;
    }
    
    if (!$user['can_apply']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Account is not eligible to apply for clearance']);
        exit;
    }
    
    // Check if clearance form already exists
    $stmt = $connection->prepare("
        SELECT clearance_form_id, status 
        FROM clearance_forms 
        WHERE user_id = ? AND academic_year_id = ? AND semester_id = ? AND clearance_type = ?
    ");
    $stmt->execute([$userId, $activePeriod['academic_year_id'], $activePeriod['semester_id'], $userSector]);
    $existingForm = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingForm) {
        if ($existingForm['status'] === 'Pending' || $existingForm['status'] === 'Processing') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Clearance application already submitted']);
            exit;
        } elseif ($existingForm['status'] === 'Approved') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Clearance already approved']);
            exit;
        }
    }
    
    // Start transaction
    $connection->beginTransaction();
    
    try {
        $clearanceFormId = null;
        
        if ($existingForm) {
            // Update existing form
            $clearanceFormId = $existingForm['clearance_form_id'];
            $stmt = $connection->prepare("
                UPDATE clearance_forms 
                SET status = 'Pending', applied_at = NOW(), updated_at = NOW()
                WHERE clearance_form_id = ?
            ");
            $stmt->execute([$clearanceFormId]);
        } else {
            // Create new form
            $clearanceFormId = generateClearanceFormId($connection);
            $stmt = $connection->prepare("
                INSERT INTO clearance_forms (
                    clearance_form_id,
                    user_id,
                    academic_year_id,
                    semester_id,
                    clearance_type,
                    status,
                    applied_at,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, 'Pending', NOW(), NOW())
            ");
            $stmt->execute([
                $clearanceFormId,
                $userId,
                $activePeriod['academic_year_id'],
                $activePeriod['semester_id'],
                $userSector
            ]);
        }
        
        // Create signatory actions for this form
        createSignatoryActionsForForm($connection, $clearanceFormId, $activePeriod['period_id']);
        
        // Log the application
        logAuditEvent($connection, $userId, 'Clearance Application Submitted', 'ClearanceForm', $clearanceFormId, null, [
            'sector' => $userSector,
            'academic_year_id' => $activePeriod['academic_year_id'],
            'semester_id' => $activePeriod['semester_id']
        ]);
        
        $connection->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Clearance application submitted successfully',
            'clearance_form_id' => $clearanceFormId,
            'sector' => $userSector,
            'period_id' => $activePeriod['period_id']
        ]);
        
    } catch (Exception $e) {
        $connection->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

// Helper function to determine user's sector
function getUserSector($connection, $userId) {
    // Check if user is a student
    $stmt = $connection->prepare("
        SELECT sector FROM students 
        WHERE user_id = ? AND account_status = 'active'
    ");
    $stmt->execute([$userId]);
    $studentSector = $stmt->fetchColumn();
    
    if ($studentSector) {
        return $studentSector;
    }
    
    // Check if user is faculty
    $stmt = $connection->prepare("
        SELECT sector FROM faculty 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $facultySector = $stmt->fetchColumn();
    
    if ($facultySector) {
        return $facultySector;
    }
    
    return null;
}

// Helper function to generate clearance form ID
function generateClearanceFormId($connection) {
    $year = date('Y');
    $stmt = $connection->query("SELECT COUNT(*) + 1 as next_id FROM clearance_forms WHERE clearance_form_id LIKE 'CF-$year-%'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $nextId = str_pad($result['next_id'], 5, '0', STR_PAD_LEFT);
    return "CF-$year-$nextId";
}

// Helper function to create signatory actions for a form
function createSignatoryActionsForForm($connection, $formId, $periodId) {
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
}

// Helper function to log audit events
function logAuditEvent($connection, $userId, $action, $entityType, $entityId, $oldValues = null, $newValues = null) {
    try {
        $stmt = $connection->prepare("
            INSERT INTO audit_logs (
                user_id, action, entity_type, entity_id, 
                old_values, new_values, ip_address, user_agent, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt->execute([
            $userId,
            $action,
            $entityType,
            $entityId,
            $oldValues ? json_encode($oldValues) : null,
            $newValues ? json_encode($newValues) : null,
            $ipAddress,
            $userAgent
        ]);
    } catch (Exception $e) {
        error_log("Error logging audit event: " . $e->getMessage());
    }
}
?>
