<?php
/**
 * Period Status API
 * Returns comprehensive period status information
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../includes/config/database.php';
require_once '../../includes/classes/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $userId = $auth->getUserId();
    $clearanceType = $_GET['clearance_type'] ?? null;
    
    // Get current user's clearance type if not specified
    if (!$clearanceType) {
        // Check if user is a student
        $studentSql = "
            SELECT s.sector 
            FROM students s 
            WHERE s.user_id = ?
        ";
        $stmt = $pdo->prepare($studentSql);
        $stmt->execute([$userId]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($student) {
            $clearanceType = $student['sector'];
        } else {
            // Check if user is faculty
            $facultySql = "
                SELECT f.sector 
                FROM faculty f 
                WHERE f.user_id = ?
            ";
            $stmt = $pdo->prepare($facultySql);
            $stmt->execute([$userId]);
            $faculty = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($faculty) {
                $clearanceType = $faculty['sector'];
            } else {
                // Check if user is staff (admin)
                $staffSql = "
                    SELECT 'Admin' as clearance_type
                    FROM staff s 
                    WHERE s.user_id = ?
                ";
                $stmt = $pdo->prepare($staffSql);
                $stmt->execute([$userId]);
                $staff = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($staff) {
                    $clearanceType = 'Admin';
                }
            }
        }
    }
    
    // Get active periods for the user's clearance type
    $periodSql = "
        SELECT 
            p.period_id,
            p.sector,
            p.status,
            p.start_date,
            p.end_date,
            p.created_at,
            p.updated_at,
            s.semester_name,
            ay.year as school_year,
            ay.academic_year_id,
            s.semester_id,
            s.is_active as semester_active
        FROM clearance_periods p
        JOIN semesters s ON p.semester_id = s.semester_id
        JOIN academic_years ay ON s.academic_year_id = ay.academic_year_id
        WHERE p.sector = ? AND ay.is_active = 1
        ORDER BY p.created_at DESC
        LIMIT 1
    ";
    
    $stmt = $pdo->prepare($periodSql);
    $stmt->execute([$clearanceType]);
    $period = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$period) {
        echo json_encode([
            'success' => true,
            'clearance_type' => $clearanceType,
            'period_status' => 'not_started',
            'period' => null,
            'grace_period' => null,
            'can_apply' => false,
            'message' => 'No clearance period found for this sector'
        ]);
        exit();
    }
    
    // Determine period status
    $effectiveStatus = $period['status'];
    $canApply = false;
    $message = '';
    
    switch ($period['status']) {
        case 'Not Started':
            $effectiveStatus = 'not_started';
            $canApply = false;
            $message = 'Clearance period has not been started yet';
            break;
            
        case 'Ongoing':
            $effectiveStatus = 'ongoing';
            $canApply = true;
            $message = 'Clearance period is active. You can apply to signatories.';
            break;
            
        case 'Paused':
            $effectiveStatus = 'paused';
            $canApply = false;
            $message = 'Clearance period is paused. Applications are disabled.';
            break;
            
        case 'Closed':
            $effectiveStatus = 'closed';
            $canApply = false;
            $message = 'Clearance period has ended. Applications are no longer accepted.';
            break;
    }
    
    // Get user's application status for this period
    $applicationSql = "
        SELECT 
            cf.clearance_form_id,
            cf.clearance_form_progress as form_status,
            cf.applied_at,
            COUNT(cs.signatory_id) as total_signatories,
            COUNT(CASE WHEN cs.action = 'Approved' THEN 1 END) as approved_count,
            COUNT(CASE WHEN cs.action = 'Rejected' THEN 1 END) as rejected_count,
            COUNT(CASE WHEN cs.action = 'Pending' THEN 1 END) as pending_count,
            COUNT(CASE WHEN cs.action = 'Unapplied' THEN 1 END) as unapplied_count
        FROM clearance_forms cf
        LEFT JOIN clearance_signatories cs ON cf.clearance_form_id = cs.clearance_form_id
        WHERE cf.user_id = ? 
        AND cf.academic_year_id = ? 
        AND cf.semester_id = ? 
        AND cf.clearance_type = ?
        GROUP BY cf.clearance_form_id
    ";
    
    $stmt = $pdo->prepare($applicationSql);
    $stmt->execute([$userId, $period['academic_year_id'], $period['semester_id'], $clearanceType]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get detailed signatory statuses
    $signatoryStatusSql = "
        SELECT 
            cs.signatory_id,
            cs.designation_id,
            cs.action,
            cs.remarks,
            cs.updated_at,
            d.designation_name,
            u.first_name,
            u.last_name
        FROM clearance_signatories cs
        JOIN designations d ON cs.designation_id = d.designation_id
        LEFT JOIN users u ON cs.actual_user_id = u.user_id
        WHERE cs.clearance_form_id = ?
        ORDER BY d.designation_name
    ";
    
    $signatoryStatuses = [];
    if ($application) {
        $stmt = $pdo->prepare($signatoryStatusSql);
        $stmt->execute([$application['clearance_form_id']]);
        $signatoryStatuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Determine button states for each signatory
    $buttonStates = [];
    foreach ($signatoryStatuses as $signatory) {
        $buttonState = determineButtonState($signatory['action'], $effectiveStatus);
        $buttonStates[] = [
            'signatory_id' => $signatory['signatory_id'],
            'designation_id' => $signatory['designation_id'],
            'designation_name' => $signatory['designation_name'],
            'signatory_name' => trim($signatory['first_name'] . ' ' . $signatory['last_name']),
            'current_action' => $signatory['action'],
            'button_state' => $buttonState,
            'can_apply' => $buttonState['enabled'],
            'can_reapply' => $buttonState['enabled'] && $signatory['action'] === 'Rejected',
            'remarks' => $signatory['remarks'],
            'updated_at' => $signatory['updated_at']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'user_id' => $userId,
        'clearance_type' => $clearanceType,
        'period_status' => $effectiveStatus,
        'period' => $period,
        'grace_period' => null,
        'can_apply' => $canApply,
        'message' => $message,
        'application' => $application,
        'signatory_statuses' => $signatoryStatuses,
        'button_states' => $buttonStates,
        'summary' => [
            'total_signatories' => $application['total_signatories'] ?? 0,
            'approved' => $application['approved_count'] ?? 0,
            'rejected' => $application['rejected_count'] ?? 0,
            'pending' => $application['pending_count'] ?? 0,
            'unapplied' => $application['unapplied_count'] ?? 0,
            'can_apply_count' => count(array_filter($buttonStates, function($state) { return $state['can_apply']; }))
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

/**
 * Determine button state based on signatory action and period status
 */
function determineButtonState($signatoryAction, $effectiveStatus) {
    $buttonState = [
        'enabled' => false,
        'text' => '',
        'class' => 'btn-secondary',
        'tooltip' => '',
        'reason' => ''
    ];
    
    switch ($effectiveStatus) {
        case 'not_started':
            $buttonState['enabled'] = false;
            $buttonState['text'] = 'Not Available';
            $buttonState['class'] = 'btn-secondary';
            $buttonState['tooltip'] = 'Clearance period has not been started yet';
            $buttonState['reason'] = 'period_not_started';
            break;
            
        case 'ongoing':
            switch ($signatoryAction) {
                case 'Unapplied':
                    $buttonState['enabled'] = true;
                    $buttonState['text'] = 'Apply';
                    $buttonState['class'] = 'btn-primary';
                    $buttonState['tooltip'] = 'Click to apply to this signatory';
                    $buttonState['reason'] = 'can_apply';
                    break;
                    
                case 'Pending':
                    $buttonState['enabled'] = false;
                    $buttonState['text'] = 'Pending';
                    $buttonState['class'] = 'btn-warning';
                    $buttonState['tooltip'] = 'Application is pending approval';
                    $buttonState['reason'] = 'pending_approval';
                    break;
                    
                case 'Approved':
                    $buttonState['enabled'] = false;
                    $buttonState['text'] = 'Approved';
                    $buttonState['class'] = 'btn-success';
                    $buttonState['tooltip'] = 'Application has been approved';
                    $buttonState['reason'] = 'approved';
                    break;
                    
                case 'Rejected':
                    $buttonState['enabled'] = true;
                    $buttonState['text'] = 'Reapply';
                    $buttonState['class'] = 'btn-danger';
                    $buttonState['tooltip'] = 'Click to reapply after rejection';
                    $buttonState['reason'] = 'can_reapply';
                    break;
            }
            break;
            
        case 'paused':
            switch ($signatoryAction) {
                case 'Unapplied':
                    $buttonState['enabled'] = false;
                    $buttonState['text'] = 'Apply';
                    $buttonState['class'] = 'btn-secondary';
                    $buttonState['tooltip'] = 'Clearance period is paused. Applications are disabled.';
                    $buttonState['reason'] = 'period_paused';
                    break;
                    
                case 'Pending':
                    $buttonState['enabled'] = false;
                    $buttonState['text'] = 'Pending';
                    $buttonState['class'] = 'btn-warning';
                    $buttonState['tooltip'] = 'Application is pending approval';
                    $buttonState['reason'] = 'pending_approval';
                    break;
                    
                case 'Approved':
                    $buttonState['enabled'] = false;
                    $buttonState['text'] = 'Approved';
                    $buttonState['class'] = 'btn-success';
                    $buttonState['tooltip'] = 'Application has been approved';
                    $buttonState['reason'] = 'approved';
                    break;
                    
                case 'Rejected':
                    $buttonState['enabled'] = false;
                    $buttonState['text'] = 'Reapply';
                    $buttonState['class'] = 'btn-secondary';
                    $buttonState['tooltip'] = 'Clearance period is paused. Reapplication will be enabled when period resumes.';
                    $buttonState['reason'] = 'period_paused_reapply';
                    break;
            }
            break;
            
        case 'closed':
            $buttonState['enabled'] = false;
            switch ($signatoryAction) {
                case 'Unapplied':
                    $buttonState['text'] = 'Not Applied';
                    $buttonState['class'] = 'btn-secondary';
                    $buttonState['tooltip'] = 'Clearance period has ended. Applications are no longer accepted.';
                    break;
                    
                case 'Pending':
                    $buttonState['text'] = 'Pending';
                    $buttonState['class'] = 'btn-warning';
                    $buttonState['tooltip'] = 'Application is pending approval';
                    break;
                    
                case 'Approved':
                    $buttonState['text'] = 'Approved';
                    $buttonState['class'] = 'btn-success';
                    $buttonState['tooltip'] = 'Application has been approved';
                    break;
                    
                case 'Rejected':
                    $buttonState['text'] = 'Rejected';
                    $buttonState['class'] = 'btn-danger';
                    $buttonState['tooltip'] = 'Application was rejected';
                    break;
            }
            $buttonState['reason'] = 'period_closed';
            break;
    }
    
    return $buttonState;
}
?>
