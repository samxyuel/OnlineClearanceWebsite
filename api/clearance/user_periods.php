<?php
// -----------------------------------------------------------------------------
// GET user's clearance periods for period selector
// Returns periods where user has clearance forms
// -----------------------------------------------------------------------------
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
    echo json_encode(['success'=>false,'message'=>'Method not allowed']); 
    exit; 
}

require_once '../../includes/config/database.php';
require_once '../../includes/classes/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401); 
    echo json_encode(['success'=>false,'message'=>'Authentication required']); 
    exit; 
}

$userId = $auth->getUserId();

try {
    $pdo = Database::getInstance()->getConnection();

    // Fetch periods where user has clearance forms
    $sql = "SELECT 
                cf.clearance_form_id,
                cf.status as form_status,
                cf.created_at as form_created,
                ay.year as school_year,
                s.semester_name,
                cp.start_date,
                cp.end_date,
                cp.status as period_status,
                cp.is_active
            FROM clearance_forms cf
            JOIN academic_years ay ON cf.academic_year_id = ay.academic_year_id
            JOIN semesters s ON cf.semester_id = s.semester_id
            LEFT JOIN clearance_periods cp ON cp.academic_year_id = cf.academic_year_id 
                AND cp.semester_id = cf.semester_id
            WHERE cf.user_id = ?
            ORDER BY cf.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $periods = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the response
    $formattedPeriods = [];
    foreach($periods as $period) {
        $formattedPeriods[] = [
            'clearance_form_id' => $period['clearance_form_id'],
            'school_year' => $period['school_year'],
            'semester_name' => $period['semester_name'],
            'period_text' => $period['school_year'] . ' ' . $period['semester_name'],
            'form_status' => $period['form_status'],
            'period_status' => $period['period_status'],
            'is_active' => (bool)$period['is_active'],
            'start_date' => $period['start_date'],
            'end_date' => $period['end_date'],
            'form_created' => $period['form_created']
        ];
    }

    echo json_encode([
        'success' => true,
        'periods' => $formattedPeriods,
        'total' => count($formattedPeriods)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
?>
