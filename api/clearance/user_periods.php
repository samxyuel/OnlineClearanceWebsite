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

    // NEW APPROACH: Show active periods even without clearance forms
    // 1. Get active period
    $activeSql = "SELECT 
                      cp.academic_year_id,
                      cp.semester_id,
                      ay.year as school_year,
                      s.semester_name,
                      cp.start_date,
                      cp.end_date,
                      cp.status as period_status,
                      cp.is_active
                  FROM clearance_periods cp
                  JOIN academic_years ay ON cp.academic_year_id = ay.academic_year_id
                  JOIN semesters s ON cp.semester_id = s.semester_id
                  WHERE cp.is_active = 1
                  LIMIT 1";
    
    $activeStmt = $pdo->query($activeSql);
    $activePeriod = $activeStmt->fetch(PDO::FETCH_ASSOC);
    
    $formattedPeriods = [];
    
    if ($activePeriod) {
        // 2. Check if user has existing clearance form for this period
        $formStmt = $pdo->prepare("
            SELECT clearance_form_id, status, created_at 
            FROM clearance_forms 
            WHERE user_id = ? AND academic_year_id = ? AND semester_id = ?
        ");
        $formStmt->execute([$userId, $activePeriod['academic_year_id'], $activePeriod['semester_id']]);
        $existingForm = $formStmt->fetch(PDO::FETCH_ASSOC);
        
        // 3. Add active period (with or without existing form)
        $formattedPeriods[] = [
            'clearance_form_id' => $existingForm ? $existingForm['clearance_form_id'] : null,
            'school_year' => $activePeriod['school_year'],
            'semester_name' => $activePeriod['semester_name'],
            'period_text' => $activePeriod['school_year'] . ' ' . $activePeriod['semester_name'],
            'form_status' => $existingForm ? $existingForm['status'] : 'Unapplied',
            'period_status' => $activePeriod['period_status'],
            'is_active' => (bool)$activePeriod['is_active'],
            'start_date' => $activePeriod['start_date'],
            'end_date' => $activePeriod['end_date'],
            'form_created' => $existingForm ? $existingForm['created_at'] : null,
            'has_existing_form' => $existingForm ? true : false
        ];
    }
    
    // 4. Also include any historical periods where user has clearance forms
    $historicalSql = "SELECT 
                        cf.clearance_form_id,
                        cf.clearance_form_progress as form_status,
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
                    WHERE cf.user_id = ? AND (cp.is_active = 0 OR cp.is_active IS NULL)
                    ORDER BY cf.created_at DESC";
    
    $historicalStmt = $pdo->prepare($historicalSql);
    $historicalStmt->execute([$userId]);
    $historicalPeriods = $historicalStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($historicalPeriods as $period) {
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
            'form_created' => $period['form_created'],
            'has_existing_form' => true
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
