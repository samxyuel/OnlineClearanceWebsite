<?php
/**
 * Rejection Reasons API
 * Provides available rejection reasons for signatory rejections
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(204); 
    exit; 
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') { 
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
    
    // Get rejection reasons from database
    $stmt = $connection->prepare("
        SELECT rejection_reason_id, reason_name, description, is_active
        FROM rejection_reasons 
        WHERE is_active = 1
        ORDER BY reason_name ASC
    ");
    $stmt->execute();
    $reasons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no reasons in database, provide default ones
    if (empty($reasons)) {
        $reasons = [
            [
                'rejection_reason_id' => 1,
                'reason_name' => 'Incomplete Documentation',
                'description' => 'Required documents are missing or incomplete',
                'is_active' => 1
            ],
            [
                'rejection_reason_id' => 2,
                'reason_name' => 'Outstanding Obligations',
                'description' => 'Student has outstanding financial or academic obligations',
                'is_active' => 1
            ],
            [
                'rejection_reason_id' => 3,
                'reason_name' => 'Disciplinary Issues',
                'description' => 'Student has unresolved disciplinary matters',
                'is_active' => 1
            ],
            [
                'rejection_reason_id' => 4,
                'reason_name' => 'Library Fines',
                'description' => 'Student has unpaid library fines or overdue books',
                'is_active' => 1
            ],
            [
                'rejection_reason_id' => 5,
                'reason_name' => 'Laboratory Clearance',
                'description' => 'Student has not completed laboratory clearance requirements',
                'is_active' => 1
            ],
            [
                'rejection_reason_id' => 6,
                'reason_name' => 'Other',
                'description' => 'Other reasons not listed above',
                'is_active' => 1
            ]
        ];
    }
    
    echo json_encode([
        'success' => true,
        'rejection_reasons' => $reasons,
        'count' => count($reasons)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
