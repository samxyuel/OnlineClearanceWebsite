<?php
/**
 * User Clearance Status API
 * 
 * This API provides the current clearance status for the logged-in user,
 * integrating with the automatic form distribution system.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
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

try {
    $connection = Database::getInstance()->getConnection();
    $userId = $auth->getUserId();
    
    // Check if specific form_id is requested
    $requestedFormId = $_GET['form_id'] ?? null;
    
    if ($requestedFormId) {
        // Fetch specific form by ID
        $sql = "
            SELECT 
                cf.clearance_form_id,
                cf.clearance_form_progress as form_status,
                cf.applied_at,
                cf.completed_at,
                cf.clearance_type,
                ay.year as academic_year,
                s.semester_name,
                s.semester_id,
                ay.academic_year_id
            FROM clearance_forms cf
            INNER JOIN academic_years ay ON cf.academic_year_id = ay.academic_year_id
            INNER JOIN semesters s ON cf.semester_id = s.semester_id
            WHERE cf.clearance_form_id = ? AND cf.user_id = ?
            LIMIT 1
        ";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute([$requestedFormId, $userId]);
        $form = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$form) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Clearance form not found']);
            exit;
        }
        
        $academicYearId = $form['academic_year_id'];
        $semesterId = $form['semester_id'];
        $clearanceType = $form['clearance_type'];
        
    } else {
        // Get the most recent clearance form for the user
        $sql = "
            SELECT 
                cf.clearance_form_id,
                cf.clearance_form_progress as form_status,
                cf.applied_at,
                cf.completed_at,
                cf.clearance_type,
                ay.year as academic_year,
                s.semester_name,
                s.semester_id,
                ay.academic_year_id
            FROM clearance_forms cf
            INNER JOIN academic_years ay ON cf.academic_year_id = ay.academic_year_id
            INNER JOIN semesters s ON cf.semester_id = s.semester_id
            WHERE cf.user_id = ?
            ORDER BY cf.created_at DESC
            LIMIT 1
        ";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute([$userId]);
        $form = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$form) {
            echo json_encode([
                'success' => true,
                'applied' => false,
                'message' => 'No clearance forms found for user',
                'signatories' => [],
                'overall_status' => 'No Form'
            ]);
            exit;
        }
        
        $academicYearId = $form['academic_year_id'];
        $semesterId = $form['semester_id'];
        $clearanceType = $form['clearance_type'];
    }
    
    // Get period status for this clearance form
    $periodStatusSql = "
        SELECT p.status as period_status
        FROM clearance_periods p
        WHERE p.sector = ? 
        AND p.semester_id = ? 
        AND p.academic_year_id = ?
        ORDER BY p.created_at DESC
        LIMIT 1
    ";
    $stmt = $connection->prepare($periodStatusSql);
    $stmt->execute([$clearanceType, $semesterId, $academicYearId]);
    $periodStatusRow = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Format the status to match frontend expectations (e.g., 'Not Started' -> 'not_started')
    $rawStatus = $periodStatusRow ? $periodStatusRow['period_status'] : 'Unknown';
    $periodStatusValue = strtolower(str_replace(' ', '_', $rawStatus));
    
    // Get sector clearance settings for required first/last logic
    $settingsSql = "
        SELECT * 
        FROM sector_clearance_settings 
        WHERE clearance_type = ? 
        LIMIT 1
    ";
    $settingsStmt = $connection->prepare($settingsSql);
    $settingsStmt->execute([$clearanceType]);
    $settings = $settingsStmt->fetch(PDO::FETCH_ASSOC) ?: null;

    // Get signatories for this clearance form
    $signatoriesSql = "
        SELECT 
            cs.signatory_id,
            cs.action,
            cs.designation_id,
            cs.remarks,
            cs.additional_remarks,
            cs.date_signed,
            cs.created_at,
            cs.updated_at,
            d.designation_name,
            u_signatory.first_name as signatory_first_name,
            u_signatory.last_name as signatory_last_name,
            u_signatory.username as signatory_username
        FROM clearance_signatories cs
        INNER JOIN designations d ON cs.designation_id = d.designation_id
        LEFT JOIN users u_signatory ON cs.actual_user_id = u_signatory.user_id
        WHERE cs.clearance_form_id = ?
        ORDER BY cs.created_at ASC
    ";
    
    $stmt = $connection->prepare($signatoriesSql);
    $stmt->execute([$form['clearance_form_id']]);
    $signatories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process signatories data
    $processedSignatories = [];
    $hasUnapplied = false;
    $hasPending = false;
    $hasRejected = false;
    $hasApproved = false;
    $allApproved = true;
    
    foreach ($signatories as $signatory) {
        $action = $signatory['action'] ?: 'Unapplied';
        
        if ($action === 'Unapplied') {
            $hasUnapplied = true;
            $allApproved = false;
        } elseif ($action === 'Pending') {
            $hasPending = true;
            $allApproved = false;
        } elseif ($action === 'Rejected') {
            $hasRejected = true;
            $allApproved = false;
        } elseif ($action === 'Approved') {
            $hasApproved = true;
        }
        
        $processedSignatories[] = [
            'signatory_id' => $signatory['signatory_id'],
            'designation_id' => $signatory['designation_id'],
            'designation_name' => $signatory['designation_name'],
            'action' => $action,
            'remarks' => $signatory['remarks'],
            'additional_remarks' => $signatory['additional_remarks'],
            'date_signed' => $signatory['date_signed'],
            'created_at' => $signatory['created_at'],
            'updated_at' => $signatory['updated_at'],
            'signatory_name' => trim(($signatory['signatory_first_name'] ?? '') . ' ' . ($signatory['signatory_last_name'] ?? '')),
            'signatory_username' => $signatory['signatory_username']
        ];
    }
    
    // Determine clearance form progress (new 3-status system)
    $clearanceFormProgress = 'unapplied';
    
    // Check if user has applied to any signatory
    $hasApplied = $hasPending || $hasApproved || $hasRejected;
    
    if ($allApproved && !$hasUnapplied && count($signatories) > 0) {
        // All signatories approved
        $clearanceFormProgress = 'complete';
    } elseif ($hasApplied) {
        // User has applied to one or more signatories
        $clearanceFormProgress = 'in-progress';
    } else {
        // User hasn't applied to any signatory yet
        $clearanceFormProgress = 'unapplied';
    }
    
    // Determine overall status (for backward compatibility)
    $overallStatus = 'Unapplied';
    if ($clearanceFormProgress === 'complete') {
        $overallStatus = 'Complete';
    } elseif ($clearanceFormProgress === 'in-progress') {
        $overallStatus = 'In Progress';
    } else {
        $overallStatus = 'Unapplied';
    }
    
    echo json_encode([
        'success' => true,
        'applied' => $form['form_status'] !== 'Unapplied',
        'form_status' => $form['form_status'],
        'overall_status' => $overallStatus,
        'clearance_form_progress' => $clearanceFormProgress,
        'clearance_form_id' => $form['clearance_form_id'],
        'academic_year' => $form['academic_year'],
        'semester_name' => $form['semester_name'],
        'clearance_type' => $clearanceType,
        'applied_at' => $form['applied_at'],
        'completed_at' => $form['completed_at'],
        'period_status' => $periodStatusValue,
        'settings' => $settings,
        'can_apply' => $periodStatusValue !== 'Closed',
        'signatories' => $processedSignatories,
        'total_signatories' => count($processedSignatories),
        'approved_count' => count(array_filter($processedSignatories, fn($s) => $s['action'] === 'Approved')),
        'pending_count' => count(array_filter($processedSignatories, fn($s) => $s['action'] === 'Pending')),
        'rejected_count' => count(array_filter($processedSignatories, fn($s) => $s['action'] === 'Rejected')),
        'unapplied_count' => count(array_filter($processedSignatories, fn($s) => $s['action'] === 'Unapplied'))
    ]);
    
} catch (Exception $e) {
    error_log("User Status API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
