<?php
/**
 * Clearance Application Status API
 * Returns the clearance application status for faculty members
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
    
    // Get query parameters
    $facultyId = $_GET['faculty_id'] ?? null;
    $clearanceType = $_GET['clearance_type'] ?? null;
    
    // Build the query
    $sql = "
        SELECT 
            ca.application_id,
            ca.user_id as faculty_id,
            ca.status,
            ca.applied_at,
            ca.completed_at,
            u.first_name,
            u.last_name,
            u.username as employee_number
        FROM clearance_applications ca
        LEFT JOIN users u ON ca.user_id = u.user_id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($facultyId) {
        $sql .= " AND ca.user_id = ?";
        $params[] = $facultyId;
    }
    
    // Note: clearance_type is not in the clearance_applications table
    // We'll need to determine this from the period or other means
    
    $sql .= " ORDER BY ca.applied_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If specific faculty_id requested, return single application or null
    if ($facultyId) {
        $application = count($applications) > 0 ? $applications[0] : null;
        echo json_encode([
            'success' => true,
            'faculty_id' => $facultyId,
            'application' => $application,
            'has_applied' => $application !== null
        ]);
    } else {
        // Return all applications
        echo json_encode([
            'success' => true,
            'applications' => $applications,
            'total' => count($applications)
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
