<?php
/**
 * Signatory Actions API - Extended Version
 * Handles both apply and signatory action (approve/reject) operations
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(204); 
    exit; 
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { 
    http_response_code(405); 
    echo json_encode(['success' => false, 'message' => 'Method not allowed']); 
    exit; 
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../includes/config/database.php';
require_once '../../includes/classes/Auth.php';
require_once '../../includes/functions/audit_functions.php';

try {
    $connection = Database::getInstance()->getConnection();
    
    // Get user ID from session (demo session support)
    $userId = $_SESSION['user_id'] ?? 118; // Fallback to demo user
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid JSON data'
        ]);
        exit;
    }
    
    // Determine operation type
    $operation = $input['operation'] ?? 'apply';
    
    switch ($operation) {
        case 'apply':
            handleApplyOperation($connection, $userId, $input);
            break;
        case 'approve':
            handleApproveOperation($connection, $userId, $input);
            break;
        case 'reject':
            handleRejectOperation($connection, $userId, $input);
            break;
        case 'bulk_approve':
            handleBulkApproveOperation($connection, $userId, $input);
            break;
        case 'bulk_reject':
            handleBulkRejectOperation($connection, $userId, $input);
            break;
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'Invalid operation. Must be: apply, approve, reject, bulk_approve, or bulk_reject'
            ]);
            exit;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

/**
 * Handle apply operation (existing functionality)
 */
function handleApplyOperation($connection, $userId, $input) {
    if (!isset($input['signatory_id']) || !isset($input['clearance_form_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'signatory_id and clearance_form_id are required for apply operation'
        ]);
        exit;
    }
    
    $signatoryId = (int)$input['signatory_id'];
    $clearanceFormId = $input['clearance_form_id'];
    
    try {
        $connection->beginTransaction();

        // 1. Verify the user owns the clearance form and get its progress
        $stmt = $connection->prepare("
            SELECT clearance_form_id, clearance_form_progress, applied_at 
            FROM clearance_forms 
            WHERE clearance_form_id = ? AND user_id = ?
        ");
        $stmt->execute([$clearanceFormId, $userId]);
        $form = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$form) {
            http_response_code(404);
            echo json_encode([
                'success' => false, 
                'message' => 'Clearance form not found or access denied'
            ]);
            $connection->rollBack();
            exit;
        }

        // 2. Check the signatory's current status
        $stmt = $connection->prepare("SELECT action FROM clearance_signatories WHERE signatory_id = ? AND clearance_form_id = ?");
        $stmt->execute([$signatoryId, $clearanceFormId]);
        $signatory = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$signatory) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Signatory not found for this clearance form.']);
            $connection->rollBack();
            exit;
        }

        // 3. Allow application only if status is 'Unapplied' or 'Rejected'
        if ($signatory['action'] !== 'Unapplied' && $signatory['action'] !== 'Rejected') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'You have already applied to this signatory. Current status: ' . $signatory['action']]);
            $connection->rollBack();
            exit;
        }
        
        // 4. Update the signatory status to 'Pending'
        $stmt = $connection->prepare("
            UPDATE clearance_signatories 
            SET action = 'Pending', updated_at = NOW() 
            WHERE signatory_id = ? AND clearance_form_id = ?
        ");
        $result = $stmt->execute([$signatoryId, $clearanceFormId]);

        if (!$result || $stmt->rowCount() === 0) {
            $connection->rollBack();
            echo json_encode([
                'success' => false,
                'message' => 'Failed to submit application. Please try again.'
            ]);
            exit;
        }

        // 5. Update the overall form progress to 'in-progress' if it's the first application
        if ($form['clearance_form_progress'] === 'unapplied') {
            $updateFormSql = "UPDATE clearance_forms SET clearance_form_progress = 'in-progress'";
            if ($form['applied_at'] === null) {
                $updateFormSql .= ", applied_at = NOW()";
            }
            $updateFormSql .= " WHERE clearance_form_id = ?";
            
            $stmt = $connection->prepare($updateFormSql);
            $stmt->execute([$clearanceFormId]);
        }
        
        // 6. Log the application
        logActivity($userId, 'Signatory Apply', [
            'form_id' => $clearanceFormId,
            'signatory_id' => $signatoryId
        ]);

        $connection->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Application submitted successfully'
        ]);

    } catch (Exception $e) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        http_response_code(500);
        error_log("Apply signatory error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred while processing your application.']);
    }
}

/**
 * Handle approve operation
 */
function handleApproveOperation($connection, $userId, $input) {
    // DEBUG: Log incoming request
    error_log('[apply_signatory.php - Approve] Request received: ' . json_encode([
        'logged_in_user_id' => $userId,
        'target_user_id' => $input['target_user_id'] ?? 'NOT SET',
        'signatory_id' => $input['signatory_id'] ?? 'NOT SET',
        'clearance_form_id' => $input['clearance_form_id'] ?? 'NOT SET',
        'school_term' => $input['school_term'] ?? 'NOT SET',
        'remarks' => $input['remarks'] ?? ''
    ]));

    if (!isset($input['target_user_id']) || !isset($input['signatory_id']) || !isset($input['clearance_form_id'])) {
        error_log('[apply_signatory.php - Approve] Missing required parameters');
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'target_user_id, signatory_id, and clearance_form_id are required for approve operation'
        ]);
        exit;
    }
    
    $targetUserId = (int)$input['target_user_id'];
    $signatoryId = (int)$input['signatory_id'];
    $clearanceFormId = $input['clearance_form_id'];
    $remarks = $input['remarks'] ?? '';
    $schoolTerm = $input['school_term'] ?? '';
    
    // DEBUG: Log parsed values
    error_log('[apply_signatory.php - Approve] Parsed values: ' . json_encode([
        'targetUserId' => $targetUserId,
        'signatoryId' => $signatoryId,
        'clearanceFormId' => $clearanceFormId,
        'schoolTerm' => $schoolTerm
    ]));
    
    // Verify the clearance form belongs to the target user
    $stmt = $connection->prepare("
        SELECT clearance_form_id, status, academic_year_id, semester_id
        FROM clearance_forms 
        WHERE clearance_form_id = ? AND user_id = ?
    ");
    $stmt->execute([$clearanceFormId, $targetUserId]);
    $form = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // DEBUG: Log form lookup result
    error_log('[apply_signatory.php - Approve] Form lookup result: ' . json_encode($form ? [
        'clearance_form_id' => $form['clearance_form_id'],
        'status' => $form['status'],
        'academic_year_id' => $form['academic_year_id'],
        'semester_id' => $form['semester_id']
    ] : 'FORM NOT FOUND'));
    
    if (!$form) {
        error_log('[apply_signatory.php - Approve] Form not found or access denied');
        http_response_code(404);
        echo json_encode([
            'success' => false, 
            'message' => 'Clearance form not found or access denied'
        ]);
        exit;
    }
    
    // DEBUG: Check if signatory exists before update
    $checkStmt = $connection->prepare("
        SELECT signatory_id, action, designation_id 
        FROM clearance_signatories 
        WHERE signatory_id = ? AND clearance_form_id = ?
    ");
    $checkStmt->execute([$signatoryId, $clearanceFormId]);
    $existingSignatory = $checkStmt->fetch(PDO::FETCH_ASSOC);
    error_log('[apply_signatory.php - Approve] Existing signatory record: ' . json_encode($existingSignatory ? [
        'signatory_id' => $existingSignatory['signatory_id'],
        'action' => $existingSignatory['action'],
        'designation_id' => $existingSignatory['designation_id']
    ] : 'SIGNATORY NOT FOUND'));

    // Update the signatory status to 'Approved'
    $stmt = $connection->prepare("
        UPDATE clearance_signatories 
        SET action = 'Approved', remarks = ?, updated_at = NOW() 
        WHERE signatory_id = ? AND clearance_form_id = ?
    ");
    $result = $stmt->execute([$remarks, $signatoryId, $clearanceFormId]);
    
    // DEBUG: Log update result
    error_log('[apply_signatory.php - Approve] Update result: ' . json_encode([
        'result' => $result,
        'rowCount' => $stmt->rowCount(),
        'affectedRows' => $stmt->rowCount()
    ]));
    
    if ($result && $stmt->rowCount() > 0) {
        // Check if all signatories are approved to update form status
        updateFormStatusIfComplete($connection, $clearanceFormId);
        
        // Log the approval
        logActivity($userId, 'Signatory Approve', [
            'form_id' => $clearanceFormId,
            'signatory_id' => $signatoryId,
            'target_user_id' => $targetUserId,
            'remarks' => $remarks,
            'school_term' => $schoolTerm
        ]);
        
        error_log('[apply_signatory.php - Approve] Approval successful');
        echo json_encode([
            'success' => true,
            'message' => 'Signatory approved successfully'
        ]);
    } else {
        error_log('[apply_signatory.php - Approve] Update failed - no rows affected');
        echo json_encode([
            'success' => false,
            'message' => 'Failed to approve signatory - no matching signatory record found'
        ]);
    }
}

/**
 * Handle reject operation
 */
function handleRejectOperation($connection, $userId, $input) {
    // DEBUG: Log incoming request
    error_log('[apply_signatory.php - Reject] Request received: ' . json_encode([
        'logged_in_user_id' => $userId,
        'target_user_id' => $input['target_user_id'] ?? 'NOT SET',
        'signatory_id' => $input['signatory_id'] ?? 'NOT SET',
        'clearance_form_id' => $input['clearance_form_id'] ?? 'NOT SET',
        'school_term' => $input['school_term'] ?? 'NOT SET',
        'remarks' => $input['remarks'] ?? '',
        'rejection_reason_id' => $input['rejection_reason_id'] ?? null
    ]));

    if (!isset($input['target_user_id']) || !isset($input['signatory_id']) || !isset($input['clearance_form_id'])) {
        error_log('[apply_signatory.php - Reject] Missing required parameters');
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'target_user_id, signatory_id, and clearance_form_id are required for reject operation'
        ]);
        exit;
    }
    
    $targetUserId = (int)$input['target_user_id'];
    $signatoryId = (int)$input['signatory_id'];
    $clearanceFormId = $input['clearance_form_id'];
    $remarks = $input['remarks'] ?? '';
    $rejectionReasonId = $input['rejection_reason_id'] ?? null;
    $schoolTerm = $input['school_term'] ?? '';
    
    // DEBUG: Log parsed values
    error_log('[apply_signatory.php - Reject] Parsed values: ' . json_encode([
        'targetUserId' => $targetUserId,
        'signatoryId' => $signatoryId,
        'clearanceFormId' => $clearanceFormId,
        'schoolTerm' => $schoolTerm
    ]));
    
    // Verify the clearance form belongs to the target user
    $stmt = $connection->prepare("
        SELECT clearance_form_id, status, academic_year_id, semester_id
        FROM clearance_forms 
        WHERE clearance_form_id = ? AND user_id = ?
    ");
    $stmt->execute([$clearanceFormId, $targetUserId]);
    $form = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // DEBUG: Log form lookup result
    error_log('[apply_signatory.php - Reject] Form lookup result: ' . json_encode($form ? [
        'clearance_form_id' => $form['clearance_form_id'],
        'status' => $form['status'],
        'academic_year_id' => $form['academic_year_id'],
        'semester_id' => $form['semester_id']
    ] : 'FORM NOT FOUND'));
    
    if (!$form) {
        error_log('[apply_signatory.php - Reject] Form not found or access denied');
        http_response_code(404);
        echo json_encode([
            'success' => false, 
            'message' => 'Clearance form not found or access denied'
        ]);
        exit;
    }
    
    // DEBUG: Check if signatory exists before update
    $checkStmt = $connection->prepare("
        SELECT signatory_id, action, designation_id 
        FROM clearance_signatories 
        WHERE signatory_id = ? AND clearance_form_id = ?
    ");
    $checkStmt->execute([$signatoryId, $clearanceFormId]);
    $existingSignatory = $checkStmt->fetch(PDO::FETCH_ASSOC);
    error_log('[apply_signatory.php - Reject] Existing signatory record: ' . json_encode($existingSignatory ? [
        'signatory_id' => $existingSignatory['signatory_id'],
        'action' => $existingSignatory['action'],
        'designation_id' => $existingSignatory['designation_id']
    ] : 'SIGNATORY NOT FOUND'));

    // Update the signatory status to 'Rejected'
    $stmt = $connection->prepare("
        UPDATE clearance_signatories 
        SET action = 'Rejected', remarks = ?, rejection_reason_id = ?, updated_at = NOW() 
        WHERE signatory_id = ? AND clearance_form_id = ?
    ");
    $result = $stmt->execute([$remarks, $rejectionReasonId, $signatoryId, $clearanceFormId]);
    
    // DEBUG: Log update result
    error_log('[apply_signatory.php - Reject] Update result: ' . json_encode([
        'result' => $result,
        'rowCount' => $stmt->rowCount(),
        'affectedRows' => $stmt->rowCount()
    ]));
    
    if ($result && $stmt->rowCount() > 0) {
        // Log the rejection
        logActivity($userId, 'Signatory Reject', [
            'form_id' => $clearanceFormId,
            'signatory_id' => $signatoryId,
            'target_user_id' => $targetUserId,
            'remarks' => $remarks,
            'rejection_reason_id' => $rejectionReasonId,
            'school_term' => $schoolTerm
        ]);
        
        error_log('[apply_signatory.php - Reject] Rejection successful');
        echo json_encode([
            'success' => true,
            'message' => 'Signatory rejected successfully'
        ]);
    } else {
        error_log('[apply_signatory.php - Reject] Update failed - no rows affected');
        echo json_encode([
            'success' => false,
            'message' => 'Failed to reject signatory - no matching signatory record found'
        ]);
    }
}

/**
 * Handle bulk approve operation
 */
function handleBulkApproveOperation($connection, $userId, $input) {
    if (!isset($input['target_user_ids']) || !isset($input['signatory_id']) || !isset($input['clearance_form_ids'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'target_user_ids, signatory_id, and clearance_form_ids are required for bulk approve operation'
        ]);
        exit;
    }
    
    $targetUserIds = $input['target_user_ids'];
    $signatoryId = (int)$input['signatory_id'];
    $clearanceFormIds = $input['clearance_form_ids'];
    $remarks = $input['remarks'] ?? '';
    
    if (!is_array($targetUserIds) || !is_array($clearanceFormIds) || count($targetUserIds) !== count($clearanceFormIds)) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'target_user_ids and clearance_form_ids must be arrays of equal length'
        ]);
        exit;
    }
    
    $successCount = 0;
    $errors = [];
    
    foreach ($targetUserIds as $index => $targetUserId) {
        $clearanceFormId = $clearanceFormIds[$index];
        
        try {
            // Verify the clearance form belongs to the target user
            $stmt = $connection->prepare("
                SELECT clearance_form_id, status 
                FROM clearance_forms 
                WHERE clearance_form_id = ? AND user_id = ?
            ");
            $stmt->execute([$clearanceFormId, $targetUserId]);
            $form = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$form) {
                $errors[] = "Clearance form $clearanceFormId not found for user $targetUserId";
                continue;
            }
            
            // Update the signatory status to 'Approved'
            $stmt = $connection->prepare("
                UPDATE clearance_signatories 
                SET action = 'Approved', remarks = ?, updated_at = NOW() 
                WHERE signatory_id = ? AND clearance_form_id = ?
            ");
            $result = $stmt->execute([$remarks, $signatoryId, $clearanceFormId]);
            
            if ($result && $stmt->rowCount() > 0) {
                // Check if all signatories are approved to update form status
                updateFormStatusIfComplete($connection, $clearanceFormId);
                $successCount++;
            } else {
                $errors[] = "Failed to approve signatory for form $clearanceFormId";
            }
        } catch (Exception $e) {
            $errors[] = "Error processing form $clearanceFormId: " . $e->getMessage();
        }
    }
    
    // Log the bulk approval
    logActivity($userId, 'Bulk Signatory Approve', [
        'signatory_id' => $signatoryId,
        'target_user_ids' => $targetUserIds,
        'clearance_form_ids' => $clearanceFormIds,
        'success_count' => $successCount,
        'total_count' => count($targetUserIds),
        'remarks' => $remarks
    ]);
    
    echo json_encode([
        'success' => $successCount > 0,
        'message' => "Bulk approval completed. $successCount of " . count($targetUserIds) . " signatories approved.",
        'success_count' => $successCount,
        'total_count' => count($targetUserIds),
        'errors' => $errors
    ]);
}

/**
 * Handle bulk reject operation
 */
function handleBulkRejectOperation($connection, $userId, $input) {
    if (!isset($input['target_user_ids']) || !isset($input['signatory_id']) || !isset($input['clearance_form_ids'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'target_user_ids, signatory_id, and clearance_form_ids are required for bulk reject operation'
        ]);
        exit;
    }
    
    $targetUserIds = $input['target_user_ids'];
    $signatoryId = (int)$input['signatory_id'];
    $clearanceFormIds = $input['clearance_form_ids'];
    $remarks = $input['remarks'] ?? '';
    $rejectionReasonId = $input['rejection_reason_id'] ?? null;
    
    if (!is_array($targetUserIds) || !is_array($clearanceFormIds) || count($targetUserIds) !== count($clearanceFormIds)) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'target_user_ids and clearance_form_ids must be arrays of equal length'
        ]);
        exit;
    }
    
    $successCount = 0;
    $errors = [];
    
    foreach ($targetUserIds as $index => $targetUserId) {
        $clearanceFormId = $clearanceFormIds[$index];
        
        try {
            // Verify the clearance form belongs to the target user
            $stmt = $connection->prepare("
                SELECT clearance_form_id, status 
                FROM clearance_forms 
                WHERE clearance_form_id = ? AND user_id = ?
            ");
            $stmt->execute([$clearanceFormId, $targetUserId]);
            $form = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$form) {
                $errors[] = "Clearance form $clearanceFormId not found for user $targetUserId";
                continue;
            }
            
            // Update the signatory status to 'Rejected'
            $stmt = $connection->prepare("
                UPDATE clearance_signatories 
                SET action = 'Rejected', remarks = ?, rejection_reason_id = ?, updated_at = NOW() 
                WHERE signatory_id = ? AND clearance_form_id = ?
            ");
            $result = $stmt->execute([$remarks, $rejectionReasonId, $signatoryId, $clearanceFormId]);
            
            if ($result && $stmt->rowCount() > 0) {
                $successCount++;
            } else {
                $errors[] = "Failed to reject signatory for form $clearanceFormId";
            }
        } catch (Exception $e) {
            $errors[] = "Error processing form $clearanceFormId: " . $e->getMessage();
        }
    }
    
    // Log the bulk rejection
    logActivity($userId, 'Bulk Signatory Reject', [
        'signatory_id' => $signatoryId,
        'target_user_ids' => $targetUserIds,
        'clearance_form_ids' => $clearanceFormIds,
        'success_count' => $successCount,
        'total_count' => count($targetUserIds),
        'remarks' => $remarks,
        'rejection_reason_id' => $rejectionReasonId
    ]);
    
    echo json_encode([
        'success' => $successCount > 0,
        'message' => "Bulk rejection completed. $successCount of " . count($targetUserIds) . " signatories rejected.",
        'success_count' => $successCount,
        'total_count' => count($targetUserIds),
        'errors' => $errors
    ]);
}

/**
 * Update form status to Complete if all signatories are approved
 */
function updateFormStatusIfComplete($connection, $clearanceFormId) {
    // Check if all signatories are approved
    $stmt = $connection->prepare("
        SELECT COUNT(*) as total, 
               COUNT(CASE WHEN action = 'Approved' THEN 1 END) as approved
        FROM clearance_signatories 
        WHERE clearance_form_id = ?
    ");
    $stmt->execute([$clearanceFormId]);
    $status = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($status['total'] > 0 && $status['approved'] == $status['total']) {
        // All signatories approved, update form status to Complete
        $stmt = $connection->prepare("
            UPDATE clearance_forms 
            SET status = 'Complete', completed_at = NOW(), updated_at = NOW() 
            WHERE clearance_form_id = ?
        ");
        $stmt->execute([$clearanceFormId]);
    }
}
?>