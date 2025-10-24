<?php
/**
 * API Endpoint to list all active sectors.
 * Used for populating dropdowns in the UI.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Adjust for production if needed
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once '../../includes/config/database.php';
require_once '../../includes/classes/Auth.php';

// Authentication check
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Fetch all active sectors from the database
    $stmt = $pdo->query("SELECT sector_id, sector_name FROM sectors ORDER BY sector_name ASC");
    $sectors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'sectors' => $sectors]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}
?>