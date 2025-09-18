<?php
/**
 * Apply to Signatory API - Simplified Version
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

try {
    $connection = Database::getInstance()->getConnection();
    
    // Get user ID from session (demo session support)
    $userId = $_SESSION['user_id'] ?? 118; // Fallback to demo user
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['signatory_id']) || !isset($input['clearance_form_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'signatory_id and clearance_form_id are required'
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
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>