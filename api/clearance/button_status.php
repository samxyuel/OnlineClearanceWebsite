<?php
/**
 * Enhanced Clearance Button Status API
 * Returns comprehensive button status information including:
 * 1. Period status
 * 2. User's application status
 * 3. Signatory button states
 * 4. Real-time status updates
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
    
    $actingUserId = $auth->getUserId(); // The user performing the check (the signatory)
    $targetUserId = null; // The user whose status is being checked (the applicant)

    $clearanceType = $_GET['clearance_type'] ?? null;
    
    // Determine the target user ID from the request
    if (isset($_GET['faculty_id'])) {
        // Resolve user_id from faculty employee_number
        $facultyId = $_GET['faculty_id'];
        $stmt = $pdo->prepare("SELECT user_id FROM faculty WHERE employee_number = ?");
        $stmt->execute([$facultyId]);
        $targetUserId = $stmt->fetchColumn();
    } elseif (isset($_GET['student_id'])) {
        // Student ID is the user_id in this context
        $targetUserId = $_GET['student_id'];
    } else {
        $targetUserId = $actingUserId; // Default to self if no target is specified
    }
    
    // Get current user's clearance type if not specified
    if (!$clearanceType) {
        $userSql = "
            SELECT r.role_name 
            FROM users u 
            JOIN roles r ON u.role_id = r.role_id 
            WHERE u.user_id = ?
        ";
        $stmt = $pdo->prepare($userSql);
        $stmt->execute([$targetUserId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            if ($user['role_name'] === 'Faculty') {
                $clearanceType = 'Faculty';
            } else {
                // For students, determine based on department
                $studentSql = "
                    SELECT sec.sector_name 
                    FROM students s 
                    JOIN departments d ON s.department_id = d.department_id 
                    JOIN sectors sec ON d.sector_id = sec.sector_id 
                    WHERE s.user_id = ?
                ";
                $stmt = $pdo->prepare($studentSql);
                $stmt->execute([$targetUserId]);
                $student = $stmt->fetch(PDO::FETCH_ASSOC);
                $clearanceType = $student ? $student['sector_name'] : null;
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
            'button_status' => [ // Add default button_status object
                'buttons_enabled' => false,
                'disabled_reasons' => ['No clearance period found for this sector']
            ],
            'message' => 'No clearance period found for this sector',
            'button_states' => [],
            'summary' => [
                'total_signatories' => 0,
                'approved' => 0,
                'rejected' => 0,
                'pending' => 0,
                'unapplied' => 0,
                'can_apply_count' => 0
            ]
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
    $stmt->execute([$targetUserId, $period['academic_year_id'], $period['semester_id'], $clearanceType]);
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
        $signatoryActionButtons = determineSignatoryActionButtons($signatory['action'], $effectiveStatus);
        
        $buttonStates[] = [
            'signatory_id' => $signatory['signatory_id'],
            'designation_id' => $signatory['designation_id'],
            'designation_name' => $signatory['designation_name'],
            'signatory_name' => trim($signatory['first_name'] . ' ' . $signatory['last_name']),
            'current_action' => $signatory['action'],
            'button_state' => $buttonState,
            'can_apply' => $buttonState['enabled'],
            'can_reapply' => $buttonState['enabled'] && $signatory['action'] === 'Rejected',
            'signatory_action_buttons' => $signatoryActionButtons,
            'remarks' => $signatory['remarks'],
            'updated_at' => $signatory['updated_at']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'user_id' => $targetUserId,
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

/**
 * Determine signatory action buttons (approve/reject) based on signatory action and period status
 */
function determineSignatoryActionButtons($signatoryAction, $effectiveStatus) {
    $approveButton = [
        'enabled' => false,
        'text' => 'Approve',
        'class' => 'btn-success',
        'tooltip' => '',
        'reason' => ''
    ];
    
    $rejectButton = [
        'enabled' => false,
        'text' => 'Reject',
        'class' => 'btn-danger',
        'tooltip' => '',
        'reason' => ''
    ];
    
    // Only show action buttons for signatories (not for students applying)
    // This function is for management interfaces where signatories can approve/reject
    
    switch ($effectiveStatus) {
        case 'not_started':
            $approveButton['enabled'] = false;
            $approveButton['tooltip'] = 'Clearance period has not been started yet';
            $approveButton['reason'] = 'period_not_started';
            $rejectButton['enabled'] = false;
            $rejectButton['tooltip'] = 'Clearance period has not been started yet';
            $rejectButton['reason'] = 'period_not_started';
            break;
            
        case 'ongoing':
            switch ($signatoryAction) {
                case 'Pending':
                    $approveButton['enabled'] = true;
                    $approveButton['tooltip'] = 'Click to approve this signatory';
                    $approveButton['reason'] = 'can_approve';
                    $rejectButton['enabled'] = true;
                    $rejectButton['tooltip'] = 'Click to reject this signatory';
                    $rejectButton['reason'] = 'can_reject';
                    break;
                    
                case 'Approved':
                    $approveButton['enabled'] = false;
                    $approveButton['text'] = 'Approved';
                    $approveButton['class'] = 'btn-success';
                    $approveButton['tooltip'] = 'Already approved';
                    $approveButton['reason'] = 'already_approved';
                    $rejectButton['enabled'] = false;
                    $rejectButton['tooltip'] = 'Cannot reject approved signatory';
                    $rejectButton['reason'] = 'already_approved';
                    break;
                    
                case 'Rejected':
                    $approveButton['enabled'] = true;
                    $approveButton['text'] = 'Re-approve';
                    $approveButton['tooltip'] = 'Click to re-approve this signatory';
                    $approveButton['reason'] = 'can_reapprove';
                    $rejectButton['enabled'] = false;
                    $rejectButton['text'] = 'Rejected';
                    $rejectButton['class'] = 'btn-danger';
                    $rejectButton['tooltip'] = 'Already rejected';
                    $rejectButton['reason'] = 'already_rejected';
                    break;
                    
                case 'Unapplied':
                    $approveButton['enabled'] = false;
                    $approveButton['tooltip'] = 'Student has not applied to this signatory yet';
                    $approveButton['reason'] = 'not_applied';
                    $rejectButton['enabled'] = false;
                    $rejectButton['tooltip'] = 'Student has not applied to this signatory yet';
                    $rejectButton['reason'] = 'not_applied';
                    break;
            }
            break;
            
        case 'paused':
            $approveButton['enabled'] = false;
            $approveButton['tooltip'] = 'Clearance period is paused. Actions are disabled.';
            $approveButton['reason'] = 'period_paused';
            $rejectButton['enabled'] = false;
            $rejectButton['tooltip'] = 'Clearance period is paused. Actions are disabled.';
            $rejectButton['reason'] = 'period_paused';
            break;
            
        case 'closed':
            $approveButton['enabled'] = false;
            $approveButton['tooltip'] = 'Clearance period has ended. Actions are disabled.';
            $approveButton['reason'] = 'period_closed';
            $rejectButton['enabled'] = false;
            $rejectButton['tooltip'] = 'Clearance period has ended. Actions are disabled.';
            $rejectButton['reason'] = 'period_closed';
            break;
    }
    
    return [
        'approve' => $approveButton,
        'reject' => $rejectButton
    ];
}
?>