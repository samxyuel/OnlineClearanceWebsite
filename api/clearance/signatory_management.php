<?php
/**
 * Signatory Management API
 * Provides data for signatory management interfaces (School Administrator, Program Head, Regular Staff)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(204); 
    exit; 
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../includes/config/database.php';

try {
    $connection = Database::getInstance()->getConnection();
    
    // Get user ID from session (demo session support)
    $userId = $_SESSION['user_id'] ?? 118; // Fallback to demo user
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        handleGetRequest($connection, $userId);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

/**
 * Handle GET requests for signatory management data
 */
function handleGetRequest($connection, $userId) {
    $action = $_GET['action'] ?? 'list';
    
    switch ($action) {
        case 'list':
            getSignatoryManagementList($connection, $userId);
            break;
        case 'signatory_assignments':
            getSignatoryAssignments($connection, $userId);
            break;
        case 'pending_actions':
            getPendingActions($connection, $userId);
            break;
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'Invalid action. Must be: list, signatory_assignments, or pending_actions'
            ]);
    }
}

/**
 * Get signatory management list (students/faculty assigned to current user's signatory roles)
 */
function getSignatoryManagementList($connection, $userId) {
    $sector = $_GET['sector'] ?? 'College';
    $userType = $_GET['user_type'] ?? 'student';
    
    // Get current user's signatory assignments
    $stmt = $connection->prepare("
        SELECT DISTINCT ssa.designation_id, d.designation_name, ssa.sector
        FROM signatory_sector_assignments ssa
        JOIN designations d ON ssa.designation_id = d.designation_id
        WHERE ssa.user_id = ? AND ssa.sector = ?
    ");
    $stmt->execute([$userId, $sector]);
    $userAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($userAssignments)) {
        echo json_encode([
            'success' => true,
            'message' => 'No signatory assignments found for current user',
            'data' => [],
            'assignments' => []
        ]);
        return;
    }
    
    $designationIds = array_column($userAssignments, 'designation_id');
    $placeholders = str_repeat('?,', count($designationIds) - 1) . '?';
    
    if ($userType === 'student') {
        // Get students assigned to current user's signatory roles
        $sql = "
            SELECT DISTINCT
                s.student_id,
                u.user_id,
                u.username,
                u.first_name,
                u.last_name,
                u.middle_name,
                u.account_status as status,
                s.sector,
                s.section,
                s.year_level,
                p.program_name as program,
                d.department_name as department,
                cf.clearance_form_id,
                cf.clearance_form_progress as clearance_form_status,
                cs.action as signatory_action,
                cs.remarks,
                cs.updated_at as signatory_updated_at,
                des.designation_name,
                des.designation_id
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            LEFT JOIN programs p ON s.program_id = p.program_id
            LEFT JOIN departments d ON p.department_id = d.department_id
            LEFT JOIN clearance_forms cf ON u.user_id = cf.user_id AND cf.clearance_form_progress != 'unapplied'
            LEFT JOIN clearance_signatories cs ON cf.clearance_form_id = cs.clearance_form_id AND cs.designation_id IN ($placeholders)
            LEFT JOIN designations des ON cs.designation_id = des.designation_id
            WHERE s.sector = ?
            ORDER BY u.last_name, u.first_name
        ";
        
        $params = array_merge($designationIds, [$sector]);
        $stmt = $connection->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } else {
        // Get faculty assigned to current user's signatory roles
        $sql = "
            SELECT DISTINCT
                u.user_id,
                u.username,
                u.first_name,
                u.last_name,
                u.middle_name,
                u.account_status,
                s.designation_id,
                d.designation_name,
                dep.department_name as department,
                cf.clearance_form_id,
                cf.clearance_form_progress as clearance_form_status,
                cs.action as signatory_action,
                cs.remarks,
                cs.updated_at as signatory_updated_at,
                des.designation_name as signatory_designation,
                des.designation_id as signatory_designation_id
            FROM users u
            JOIN staff s ON u.user_id = s.user_id
            JOIN designations d ON s.designation_id = d.designation_id
            LEFT JOIN departments dep ON s.department_id = dep.department_id
            LEFT JOIN clearance_forms cf ON u.user_id = cf.user_id AND cf.clearance_form_progress != 'unapplied'
            LEFT JOIN clearance_signatories cs ON cf.clearance_form_id = cs.clearance_form_id AND cs.designation_id IN ($placeholders)
            LEFT JOIN designations des ON cs.designation_id = des.designation_id
            WHERE u.role_id = 4 AND s.designation_id IN ($placeholders)
            ORDER BY u.last_name, u.first_name
        ";
        
        $params = array_merge($designationIds, $designationIds);
        $stmt = $connection->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'assignments' => $userAssignments,
        'sector' => $sector,
        'user_type' => $userType,
        'count' => count($data)
    ]);
}

/**
 * Get signatory assignments for current user
 */
function getSignatoryAssignments($connection, $userId) {
    $stmt = $connection->prepare("
        SELECT 
            ssa.assignment_id,
            ssa.designation_id,
            d.designation_name,
            ssa.sector,
            ssa.assigned_at,
            ssa.is_active
        FROM signatory_sector_assignments ssa
        JOIN designations d ON ssa.designation_id = d.designation_id
        WHERE ssa.user_id = ? AND ssa.is_active = 1
        ORDER BY ssa.sector, d.designation_name
    ");
    $stmt->execute([$userId]);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'assignments' => $assignments,
        'count' => count($assignments)
    ]);
}

/**
 * Get pending actions for current user's signatory roles
 */
function getPendingActions($connection, $userId) {
    $sector = $_GET['sector'] ?? 'College';
    
    // Get current user's signatory assignments
    $stmt = $connection->prepare("
        SELECT DISTINCT ssa.designation_id, d.designation_name
        FROM signatory_sector_assignments ssa
        JOIN designations d ON ssa.designation_id = d.designation_id
        WHERE ssa.user_id = ? AND ssa.sector = ? AND ssa.is_active = 1
    ");
    $stmt->execute([$userId, $sector]);
    $userAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($userAssignments)) {
        echo json_encode([
            'success' => true,
            'pending_actions' => [],
            'count' => 0
        ]);
        return;
    }
    
    $designationIds = array_column($userAssignments, 'designation_id');
    $placeholders = str_repeat('?,', count($designationIds) - 1) . '?';
    
    // Get pending actions
    $stmt = $connection->prepare("
        SELECT 
            cs.signatory_id,
            cs.clearance_form_id,
            cs.designation_id,
            d.designation_name,
            cs.action,
            cs.remarks,
            cs.updated_at,
            cf.user_id as target_user_id,
            u.first_name,
            u.last_name,
            u.username,
            cf.clearance_form_progress as clearance_form_status
        FROM clearance_signatories cs
        JOIN designations d ON cs.designation_id = d.designation_id
        JOIN clearance_forms cf ON cs.clearance_form_id = cf.clearance_form_id
        JOIN users u ON cf.user_id = u.user_id
        WHERE cs.designation_id IN ($placeholders) 
        AND cs.action = 'Pending'
        ORDER BY cs.updated_at DESC
    ");
    $stmt->execute($designationIds);
    $pendingActions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'pending_actions' => $pendingActions,
        'count' => count($pendingActions),
        'assignments' => $userAssignments
    ]);
}
?>
