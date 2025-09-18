<?php
/**
 * Clearance Form Distribution System
 * 
 * This API handles the automatic creation and distribution of clearance forms
 * when a clearance period starts for a specific sector.
 * 
 * Features:
 * - Creates clearance forms for all eligible users in the sector
 * - Assigns signatories based on sector_signatory_assignments
 * - Implements Required First/Last signatory ordering
 * - Handles Program Head assignments for specific departments
 */

require_once __DIR__ . '/../../includes/config/database.php';
require_once __DIR__ . '/../../includes/functions/helpers.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $connection = Database::getInstance()->getConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        handleFormDistribution($connection);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    error_log("❌ FORM DISTRIBUTION ERROR: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

/**
 * Handle clearance form distribution for a sector
 */
function handleFormDistribution($connection) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        return;
    }
    
    // Validate required fields
    $requiredFields = ['clearance_type', 'academic_year_id', 'semester_id'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
            return;
        }
    }
    
    $clearanceType = $input['clearance_type'];
    $academicYearId = (int)$input['academic_year_id'];
    $semesterId = (int)$input['semester_id'];
    
    error_log("🚀 FORM DISTRIBUTION: Starting distribution for $clearanceType, AY: $academicYearId, Semester: $semesterId");
    
    // Validate clearance type
    $validTypes = ['College', 'Senior High School', 'Faculty'];
    if (!in_array($clearanceType, $validTypes)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid clearance type. Must be one of: ' . implode(', ', $validTypes)]);
        return;
    }
    
    // Start transaction
    $connection->beginTransaction();
    
    try {
        // Step 1: Get all eligible users for this sector
        $eligibleUsers = getEligibleUsers($connection, $clearanceType);
        error_log("👥 FORM DISTRIBUTION: Found " . count($eligibleUsers) . " eligible users for $clearanceType");
        
        if (empty($eligibleUsers)) {
            $connection->rollback();
            echo json_encode([
                'success' => true, 
                'message' => "No eligible users found for $clearanceType clearance",
                'forms_created' => 0,
                'signatories_assigned' => 0
            ]);
            return;
        }
        
        // Step 2: Get sector signatory assignments
        $signatoryAssignments = getSectorSignatoryAssignments($connection, $clearanceType);
        error_log("📝 FORM DISTRIBUTION: Found " . count($signatoryAssignments) . " signatory assignments for $clearanceType");
        
        if (empty($signatoryAssignments)) {
            $connection->rollback();
            echo json_encode([
                'success' => false, 
                'message' => "No signatory assignments found for $clearanceType. Please assign signatories first."
            ]);
            return;
        }
        
        // Step 3: Create clearance forms for all eligible users
        $formsCreated = 0;
        $signatoriesAssigned = 0;
        
        foreach ($eligibleUsers as $user) {
            // Check if form already exists
            $existingForm = checkExistingForm($connection, $user['user_id'], $academicYearId, $semesterId, $clearanceType);
            
            if ($existingForm) {
                error_log("⚠️ FORM DISTRIBUTION: Form already exists for user {$user['user_id']} ({$user['first_name']} {$user['last_name']})");
                continue;
            }
            
            // Create clearance form
            $formId = createClearanceForm($connection, $user, $academicYearId, $semesterId, $clearanceType);
            $formsCreated++;
            
            // Assign signatories to the form
            $assignedCount = assignSignatoriesToForm($connection, $formId, $signatoryAssignments, $user, $clearanceType);
            $signatoriesAssigned += $assignedCount;
            
            error_log("✅ FORM DISTRIBUTION: Created form $formId for {$user['first_name']} {$user['last_name']} with $assignedCount signatories");
        }
        
        // Commit transaction
        $connection->commit();
        
        error_log("🎉 FORM DISTRIBUTION: Successfully distributed $formsCreated forms with $signatoriesAssigned total signatory assignments");
        
        echo json_encode([
            'success' => true,
            'message' => "Successfully distributed clearance forms for $clearanceType",
            'forms_created' => $formsCreated,
            'signatories_assigned' => $signatoriesAssigned,
            'eligible_users' => count($eligibleUsers)
        ]);
        
    } catch (Exception $e) {
        $connection->rollback();
        error_log("❌ FORM DISTRIBUTION ERROR: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Get all eligible users for a specific clearance type
 */
function getEligibleUsers($connection, $clearanceType) {
    $sql = "";
    $params = [];
    
    switch ($clearanceType) {
        case 'College':
            $sql = "
                SELECT DISTINCT u.user_id, u.first_name, u.last_name, u.username,
                       s.program, s.department_id, d.department_name
                FROM users u
                INNER JOIN students s ON u.user_id = s.user_id
                INNER JOIN departments d ON s.department_id = d.department_id
                INNER JOIN sectors sec ON d.sector_id = sec.sector_id
                WHERE sec.sector_name = 'College'
                AND u.is_active = 1
                AND s.is_active = 1
                ORDER BY u.last_name, u.first_name
            ";
            break;
            
        case 'Senior High School':
            $sql = "
                SELECT DISTINCT u.user_id, u.first_name, u.last_name, u.username,
                       s.program, s.department_id, d.department_name
                FROM users u
                INNER JOIN students s ON u.user_id = s.user_id
                INNER JOIN departments d ON s.department_id = d.department_id
                INNER JOIN sectors sec ON d.sector_id = sec.sector_id
                WHERE sec.sector_name = 'Senior High School'
                AND u.is_active = 1
                AND s.is_active = 1
                ORDER BY u.last_name, u.first_name
            ";
            break;
            
        case 'Faculty':
            $sql = "
                SELECT DISTINCT u.user_id, u.first_name, u.last_name, u.username,
                       f.employment_status, f.department_id, d.department_name
                FROM users u
                INNER JOIN faculty f ON u.user_id = f.user_id
                INNER JOIN departments d ON f.department_id = d.department_id
                INNER JOIN sectors sec ON d.sector_id = sec.sector_id
                WHERE sec.sector_name = 'Faculty'
                AND u.is_active = 1
                AND f.is_active = 1
                ORDER BY u.last_name, u.first_name
            ";
            break;
    }
    
    $stmt = $connection->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get sector signatory assignments
 */
function getSectorSignatoryAssignments($connection, $clearanceType) {
    $sql = "
        SELECT 
            ssa.assignment_id,
            ssa.clearance_type,
            ssa.user_id,
            ssa.designation_id,
            ssa.is_program_head,
            ssa.department_id,
            ssa.is_required_first,
            ssa.is_required_last,
            ssa.is_active,
            u.first_name,
            u.last_name,
            d.designation_name,
            dept.department_name
        FROM sector_signatory_assignments ssa
        LEFT JOIN users u ON ssa.user_id = u.user_id
        LEFT JOIN designations d ON ssa.designation_id = d.designation_id
        LEFT JOIN departments dept ON ssa.department_id = dept.department_id
        WHERE ssa.clearance_type = ?
        AND ssa.is_active = 1
        ORDER BY ssa.is_required_first DESC, ssa.is_required_last ASC, d.designation_name
    ";
    
    $stmt = $connection->prepare($sql);
    $stmt->execute([$clearanceType]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Check if clearance form already exists
 */
function checkExistingForm($connection, $userId, $academicYearId, $semesterId, $clearanceType) {
    $sql = "
        SELECT clearance_form_id FROM clearance_forms 
        WHERE user_id = ? AND academic_year_id = ? AND semester_id = ? AND clearance_type = ?
    ";
    
    $stmt = $connection->prepare($sql);
    $stmt->execute([$userId, $academicYearId, $semesterId, $clearanceType]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Create a clearance form for a user
 */
function createClearanceForm($connection, $user, $academicYearId, $semesterId, $clearanceType) {
    // Generate clearance form ID using the existing trigger
    $sql = "
        INSERT INTO clearance_forms (
            user_id,
            academic_year_id,
            semester_id,
            clearance_type,
            status,
            created_at
        ) VALUES (?, ?, ?, ?, 'Unapplied', NOW())
    ";
    
    $stmt = $connection->prepare($sql);
    $stmt->execute([
        $user['user_id'],
        $academicYearId,
        $semesterId,
        $clearanceType
    ]);
    
    return $connection->lastInsertId();
}

/**
 * Assign signatories to a clearance form
 */
function assignSignatoriesToForm($connection, $formId, $signatoryAssignments, $user, $clearanceType) {
    $assignedCount = 0;
    
    // Get the clearance form ID (varchar format)
    $stmt = $connection->prepare("SELECT clearance_form_id FROM clearance_forms WHERE clearance_form_id = ?");
    $stmt->execute([$formId]);
    $form = $stmt->fetch(PDO::FETCH_ASSOC);
    $clearanceFormId = $form['clearance_form_id'];
    
    foreach ($signatoryAssignments as $assignment) {
        // Skip if this is a Program Head assignment and user doesn't belong to that department
        if ($assignment['is_program_head'] && $assignment['department_id'] != $user['department_id']) {
            continue;
        }
        
        // Create signatory entry
        $sql = "
            INSERT INTO clearance_signatories (
                clearance_form_id,
                designation_id,
                actual_user_id,
                action,
                created_at
            ) VALUES (?, ?, ?, 'Pending', NOW())
        ";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute([
            $clearanceFormId,
            $assignment['designation_id'],
            $assignment['user_id']
        ]);
        
        $assignedCount++;
    }
    
    return $assignedCount;
}

/**
 * Get clearance form distribution statistics
 */
function getDistributionStats($connection, $clearanceType, $academicYearId, $semesterId) {
    $sql = "
        SELECT 
            COUNT(*) as total_forms,
            COUNT(CASE WHEN status = 'Unapplied' THEN 1 END) as unapplied_forms,
            COUNT(CASE WHEN status = 'Applied' THEN 1 END) as applied_forms,
            COUNT(CASE WHEN status = 'In Progress' THEN 1 END) as in_progress_forms,
            COUNT(CASE WHEN status = 'Completed' THEN 1 END) as completed_forms,
            COUNT(CASE WHEN status = 'Rejected' THEN 1 END) as rejected_forms
        FROM clearance_forms
        WHERE clearance_type = ? AND academic_year_id = ? AND semester_id = ?
    ";
    
    $stmt = $connection->prepare($sql);
    $stmt->execute([$clearanceType, $academicYearId, $semesterId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
