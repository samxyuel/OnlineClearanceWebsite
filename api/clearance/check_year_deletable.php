<?php
/**
 * Check if Academic Year is Deletable
 * Pre-validation endpoint to check if an academic year can be deleted
 * Returns detailed information about why deletion may be blocked
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(204); 
    exit; 
}

require_once '../../includes/config/database.php';
require_once '../../includes/classes/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Check if user is admin (role_id = 1)
session_start();
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Get academic year ID
    $ayId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($ayId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Academic year ID is required']);
        exit;
    }

    // Verify year exists
    $stmt = $pdo->prepare('SELECT academic_year_id, year, is_active FROM academic_years WHERE academic_year_id = ?');
    $stmt->execute([$ayId]);
    $year = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$year) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Academic year not found']);
        exit;
    }

    $canDelete = true;
    $reasons = [];
    $impact = [
        'academic_year' => $year['year'],
        'semester_count' => 0,
        'period_count' => 0,
        'form_count' => 0,
        'application_count' => 0
    ];

    // Check 1: Is currently active?
    if ((int)$year['is_active'] === 1) {
        $canDelete = false;
        $reasons[] = 'This is the currently active academic year. Please activate a different year first.';
    }

    // Get semester count
    $semStmt = $pdo->prepare('SELECT COUNT(*) FROM semesters WHERE academic_year_id = ?');
    $semStmt->execute([$ayId]);
    $impact['semester_count'] = (int)$semStmt->fetchColumn();

    // Check 2: Are all periods ended/closed?
    $notEndedStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM clearance_periods 
        WHERE academic_year_id = ? 
        AND (ended_at IS NULL OR status NOT IN ('Closed', 'ended', 'Completed'))
    ");
    $notEndedStmt->execute([$ayId]);
    $notEndedCount = (int)$notEndedStmt->fetchColumn();
    
    // Get total period count
    $periodStmt = $pdo->prepare('SELECT COUNT(*) FROM clearance_periods WHERE academic_year_id = ?');
    $periodStmt->execute([$ayId]);
    $impact['period_count'] = (int)$periodStmt->fetchColumn();
    
    if ($notEndedCount > 0) {
        $canDelete = false;
        $reasons[] = "{$notEndedCount} clearance period(s) must be closed/ended first.";
    }

    // Get clearance forms count
    $formsStmt = $pdo->prepare('SELECT COUNT(*) FROM clearance_forms WHERE academic_year_id = ?');
    $formsStmt->execute([$ayId]);
    $impact['form_count'] = (int)$formsStmt->fetchColumn();

    // Get clearance applications count (legacy)
    $appsStmt = $pdo->prepare('
        SELECT COUNT(*) 
        FROM clearance_applications 
        WHERE period_id IN (SELECT period_id FROM clearance_periods WHERE academic_year_id = ?)
    ');
    $appsStmt->execute([$ayId]);
    $impact['application_count'] = (int)$appsStmt->fetchColumn();

    // Build response
    $response = [
        'success' => true,
        'can_delete' => $canDelete,
        'academic_year_id' => $ayId,
        'academic_year' => $year['year'],
        'impact' => $impact,
        'message' => $canDelete 
            ? "Academic year '{$year['year']}' can be deleted. This will permanently delete {$impact['semester_count']} semester(s), {$impact['period_count']} clearance period(s), and {$impact['form_count']} clearance form(s)."
            : "Academic year '{$year['year']}' cannot be deleted: " . implode(' ', $reasons),
        'reasons' => $reasons
    ];

    echo json_encode($response);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>

