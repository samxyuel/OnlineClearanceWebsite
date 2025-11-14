<?php
/**
 * Past Clearances API
 * Fetches historical clearance periods with statistics (Status, Applications, Completed)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
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

$db = Database::getInstance();
$connection = $db->getConnection();

// Handle GET request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    handleGetPastClearances($connection);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

/**
 * Get past clearances for a sector with statistics
 */
function handleGetPastClearances($connection) {
    try {
        $sector = $_GET['sector'] ?? null;
        
        if (!$sector) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Sector parameter is required']);
            return;
        }
        
        // Validate sector
        $validSectors = ['College', 'Senior High School', 'Faculty'];
        if (!in_array($sector, $validSectors)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid sector. Must be one of: ' . implode(', ', $validSectors)]);
            return;
        }
        
        // Query to get closed/completed clearance periods with statistics
        $sql = "SELECT 
                    cp.period_id,
                    cp.academic_year_id,
                    cp.semester_id,
                    cp.sector,
                    cp.status,
                    cp.start_date,
                    cp.end_date,
                    cp.ended_at,
                    cp.created_at,
                    cp.updated_at,
                    ay.year as academic_year,
                    s.semester_name,
                    COUNT(DISTINCT cf.clearance_form_id) as total_applications,
                    SUM(CASE WHEN cf.clearance_form_progress = 'complete' THEN 1 ELSE 0 END) as completed_applications
                FROM clearance_periods cp
                JOIN academic_years ay ON cp.academic_year_id = ay.academic_year_id
                JOIN semesters s ON cp.semester_id = s.semester_id
                LEFT JOIN clearance_forms cf ON (
                    cf.academic_year_id = cp.academic_year_id 
                    AND cf.semester_id = cp.semester_id 
                    AND cf.clearance_type = cp.sector
                )
                WHERE cp.sector = ?
                  AND (cp.status = 'Closed' OR cp.status = 'Completed' OR cp.ended_at IS NOT NULL)
                GROUP BY cp.period_id, cp.academic_year_id, cp.semester_id, cp.sector, 
                         cp.status, cp.start_date, cp.end_date, cp.ended_at, 
                         cp.created_at, cp.updated_at, ay.year, s.semester_name
                ORDER BY cp.created_at DESC";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute([$sector]);
        $periods = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format the data
        $formattedPeriods = array_map(function($period) {
            return [
                'period_id' => (int)$period['period_id'],
                'academic_year_id' => (int)$period['academic_year_id'],
                'semester_id' => (int)$period['semester_id'],
                'sector' => $period['sector'],
                'status' => $period['status'],
                'start_date' => $period['start_date'],
                'end_date' => $period['end_date'],
                'ended_at' => $period['ended_at'],
                'academic_year' => $period['academic_year'],
                'semester_name' => $period['semester_name'],
                'total_applications' => (int)$period['total_applications'],
                'completed_applications' => (int)$period['completed_applications'],
                // Keep backward compatibility
                'total_forms' => (int)$period['total_applications'],
                'completed_forms' => (int)$period['completed_applications']
            ];
        }, $periods);
        
        echo json_encode([
            'success' => true,
            'periods' => $formattedPeriods,
            'total' => count($formattedPeriods),
            'sector' => $sector
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>

