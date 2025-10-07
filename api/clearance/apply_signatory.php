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
    
    // Verify the clearance form belongs to the user
    $stmt = $connection->prepare("
        SELECT clearance_form_id, status 
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
        exit;
    }
    
    // Update the signatory status to 'Pending'
    $stmt = $connection->prepare("
        UPDATE clearance_signatories 
        SET action = 'Pending', updated_at = NOW() 
        WHERE signatory_id = ? AND clearance_form_id = ?
    ");
    $result = $stmt->execute([$signatoryId, $clearanceFormId]);
    
    if ($result && $stmt->rowCount() > 0) {
        // Log the application
        logActivity($userId, 'Signatory Apply', [
            'form_id' => $clearanceFormId,
            'signatory_id' => $signatoryId
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Application submitted successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to submit application'
        ]);
    }
}

/**
 * Handle approve operation
 */
function handleApproveOperation($connection, $userId, $input) {
    if (!isset($input['target_user_id']) || !isset($input['signatory_id']) || !isset($input['clearance_form_id'])) {
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
    
    // Verify the clearance form belongs to the target user
    $stmt = $connection->prepare("
        SELECT clearance_form_id, status 
        FROM clearance_forms 
        WHERE clearance_form_id = ? AND user_id = ?
    ");
    $stmt->execute([$clearanceFormId, $targetUserId]);
    $form = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$form) {
        http_response_code(404);
        echo json_encode([
            'success' => false, 
            'message' => 'Clearance form not found or access denied'
        ]);
        exit;
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
        
        // Log the approval
        logActivity($userId, 'Signatory Approve', [
            'form_id' => $clearanceFormId,
            'signatory_id' => $signatoryId,
            'target_user_id' => $targetUserId,
            'remarks' => $remarks
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Signatory approved successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to approve signatory'
        ]);
    }
}

/**
 * Handle reject operation
 */
function handleRejectOperation($connection, $userId, $input) {
    if (!isset($input['target_user_id']) || !isset($input['signatory_id']) || !isset($input['clearance_form_id'])) {
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
    
    // Verify the clearance form belongs to the target user
    $stmt = $connection->prepare("
        SELECT clearance_form_id, status 
        FROM clearance_forms 
        WHERE clearance_form_id = ? AND user_id = ?
    ");
    $stmt->execute([$clearanceFormId, $targetUserId]);
    $form = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$form) {
        http_response_code(404);
        echo json_encode([
            'success' => false, 
            'message' => 'Clearance form not found or access denied'
        ]);
        exit;
    }
    
    // Update the signatory status to 'Rejected'
    $stmt = $connection->prepare("
        UPDATE clearance_signatories 
        SET action = 'Rejected', remarks = ?, rejection_reason_id = ?, updated_at = NOW() 
        WHERE signatory_id = ? AND clearance_form_id = ?
    ");
    $result = $stmt->execute([$remarks, $rejectionReasonId, $signatoryId, $clearanceFormId]);
    
    if ($result && $stmt->rowCount() > 0) {
        // Log the rejection
        logActivity($userId, 'Signatory Reject', [
            'form_id' => $clearanceFormId,
            'signatory_id' => $signatoryId,
            'target_user_id' => $targetUserId,
            'remarks' => $remarks,
            'rejection_reason_id' => $rejectionReasonId
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Signatory rejected successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to reject signatory'
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