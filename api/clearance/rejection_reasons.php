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
    
    // Check if we are fetching details for a specific signatory's rejection
    $signatoryId = $_GET['signatory_id'] ?? null;

    if ($signatoryId) {
        $stmt = $connection->prepare("
            SELECT reason_id, additional_remarks
            FROM clearance_signatories
            WHERE signatory_id = :signatory_id
            LIMIT 1
        ");
        $stmt->execute(['signatory_id' => $signatoryId]);
        $details = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($details) {
            echo json_encode([
                'success' => true,
                'details' => [
                    'reason_id' => $details['reason_id'] ?? null,
                    'additional_remarks' => $details['additional_remarks'] ?? ''
                ]
            ]);
        } else {
            // No rejection details found, but not an error.
            echo json_encode([
                'success' => true,
                'details' => null
            ]);
        }
        exit;
    }

    // Get rejection reasons from database
    $stmt = $connection->prepare("
        SELECT reason_id, reason_name, reason_category, is_active
        FROM rejection_reasons 
        WHERE is_active = 1
        ORDER BY reason_name ASC
    ");
    $stmt->execute();
    $reasons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no reasons in database, provide default ones
    // Also, filter by the requested category if provided
    $category = $_GET['category'] ?? null;

    if ($category && !empty($reasons)) {
        $reasons = array_values(array_filter($reasons, function($reason) use ($category) {
            return $reason['reason_category'] === $category || $reason['reason_category'] === 'both';
        }));
    }

    if (empty($reasons)) {
        $defaultReasons = [
            [
                'reason_id' => 1,
                'reason_name' => 'Incomplete Documentation',
                'reason_category' => 'student',
                'is_active' => 1
            ],
            [
                'reason_id' => 2,
                'reason_name' => 'Unpaid Fees',
                'reason_category' => 'both',
                'is_active' => 1
            ],
            [
                'reason_id' => 3,
                'reason_name' => 'Academic Requirements Not Met',
                'reason_category' => 'both',
                'is_active' => 1
            ],
            [
                'reason_id' => 4,
                'reason_name' => 'Disciplinary Issues',
                'reason_category' => 'both',
                'is_active' => 1
            ],
            [
                'reason_id' => 5,
                'reason_name' => 'Missing Clearance Items',
                'reason_category' => 'both',
                'is_active' => 1
            ],
            [
                'reason_id' => 6,
                'reason_name' => 'Unreturned University Property',
                'reason_category' => 'faculty',
                'is_active' => 1
            ],
            [
                'reason_id' => 7,
                'reason_name' => 'Other',
                'reason_category' => 'both',
                'is_active' => 1
            ]
        ];
    }
    
    if ($category && !empty($defaultReasons)) {
        $reasons = array_filter($defaultReasons, function($reason) use ($category) {
            return $reason['reason_category'] === $category || $reason['reason_category'] === 'both';
        });
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
