<?php
/**
 * User Clearance Forms API
 * 
 * This API fetches the user's clearance forms and their assigned signatories.
 * It integrates with the automatic form distribution system.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
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

try {
    $connection = Database::getInstance()->getConnection();
    $userId = $auth->getUserId();
    
    // Get user's clearance forms with their signatories
    $sql = "
        SELECT 
            cf.clearance_form_id,
            cf.clearance_form_progress as form_status,
            cf.applied_at,
            cf.completed_at,
            cf.created_at as form_created_at,
            ay.year as academic_year,
            s.semester_name,
            cf.clearance_type,
            
            -- Signatory information
            cs.signatory_id,
            cs.action as signatory_action,
            cs.remarks as signatory_remarks,
            cs.date_signed,
            cs.created_at as signatory_created_at,
            cs.updated_at as signatory_updated_at,
            
            -- Designation and signatory details
            d.designation_name,
            u_signatory.first_name as signatory_first_name,
            u_signatory.last_name as signatory_last_name,
            u_signatory.username as signatory_username,
            
            -- User's program/department info
            COALESCE(st.program_id, f.department_id) as user_department_id,
            dept.department_name,
            prog.program_name
            
        FROM clearance_forms cf
        INNER JOIN academic_years ay ON cf.academic_year_id = ay.academic_year_id
        INNER JOIN semesters s ON cf.semester_id = s.semester_id
        LEFT JOIN clearance_signatories cs ON cf.clearance_form_id = cs.clearance_form_id
        LEFT JOIN designations d ON cs.designation_id = d.designation_id
        LEFT JOIN users u_signatory ON cs.actual_user_id = u_signatory.user_id
        
        -- Join user's department/program info
        LEFT JOIN students st ON cf.user_id = st.user_id
        LEFT JOIN faculty f ON cf.user_id = f.user_id
        LEFT JOIN departments dept ON COALESCE(st.department_id, f.department_id) = dept.department_id
        LEFT JOIN programs prog ON st.program_id = prog.program_id
        
        WHERE cf.user_id = ?
        ORDER BY 
            cf.created_at DESC,
            cs.created_at ASC
    ";
    
    $stmt = $connection->prepare($sql);
    $stmt->execute([$userId]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($results)) {
        echo json_encode([
            'success' => true,
            'message' => 'No clearance forms found',
            'forms' => [],
            'current_form' => null
        ]);
        exit;
    }
    
    // Group results by clearance form
    $forms = [];
    foreach ($results as $row) {
        $formId = $row['clearance_form_id'];
        
        if (!isset($forms[$formId])) {
            $forms[$formId] = [
                'clearance_form_id' => $formId,
                'form_status' => $row['form_status'],
                'applied_at' => $row['applied_at'],
                'completed_at' => $row['completed_at'],
                'form_created_at' => $row['form_created_at'],
                'academic_year' => $row['academic_year'],
                'semester_name' => $row['semester_name'],
                'clearance_type' => $row['clearance_type'],
                'user_department' => $row['department_name'],
                'user_program' => $row['program_name'],
                'signatories' => []
            ];
        }
        
        // Add signatory if exists
        if ($row['signatory_id']) {
            $forms[$formId]['signatories'][] = [
                'signatory_id' => $row['signatory_id'],
                'designation_name' => $row['designation_name'],
                'action' => $row['signatory_action'] ?: 'Unapplied',
                'remarks' => $row['signatory_remarks'],
                'date_signed' => $row['date_signed'],
                'signatory_created_at' => $row['signatory_created_at'],
                'signatory_updated_at' => $row['signatory_updated_at'],
                'signatory_name' => trim(($row['signatory_first_name'] ?? '') . ' ' . ($row['signatory_last_name'] ?? '')),
                'signatory_username' => $row['signatory_username']
            ];
        }
    }
    
    // Convert to array and find current form
    $formsArray = array_values($forms);
    $currentForm = null;
    
    // Find the most recent form or active form
    foreach ($formsArray as $form) {
        if ($form['form_status'] === 'Unapplied' || $form['form_status'] === 'Applied' || $form['form_status'] === 'In Progress') {
            $currentForm = $form;
            break;
        }
    }
    
    // If no active form, use the most recent one
    if (!$currentForm && !empty($formsArray)) {
        $currentForm = $formsArray[0];
    }
    
    echo json_encode([
        'success' => true,
        'forms' => $formsArray,
        'current_form' => $currentForm,
        'total_forms' => count($formsArray)
    ]);
    
} catch (Exception $e) {
    error_log("User Clearance Forms API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
