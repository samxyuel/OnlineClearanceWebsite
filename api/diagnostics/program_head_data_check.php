<?php
/**
 * Diagnostic Tool: Program Head Data Visibility Check
 * 
 * This script checks all conditions required for Program Head pages to display data.
 * It helps identify why students/faculty data might not be showing.
 * 
 * Usage: 
 *   - Browser: api/diagnostics/program_head_data_check.php?user_id=123
 *   - Command line: php api/diagnostics/program_head_data_check.php --user_id=123
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/config/database.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

// Get user_id from query parameter or command line argument
$userId = null;
if (php_sapi_name() === 'cli') {
    // Command line mode
    $options = getopt('', ['user_id:', 'type:', 'school_term:']);
    $userId = isset($options['user_id']) ? (int)$options['user_id'] : null;
    $type = isset($options['type']) ? $options['type'] : 'student';
    $schoolTerm = isset($options['school_term']) ? $options['school_term'] : '';
} else {
    // Web mode - require authentication
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required.']);
        exit;
    }
    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $auth->getUserId();
    $type = $_GET['type'] ?? 'student';
    $schoolTerm = $_GET['school_term'] ?? '';
}

if (!$userId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID is required.']);
    exit;
}

try {
    $pdo = Database::getInstance()->getConnection();
    $results = [
        'user_id' => $userId,
        'type' => $type,
        'school_term' => $schoolTerm,
        'checks' => [],
        'summary' => []
    ];

    // ============================================
    // CHECK 1: User Role
    // ============================================
    $roleCheck = [
        'name' => 'User Role Check',
        'passed' => false,
        'details' => []
    ];
    
    $roleStmt = $pdo->prepare("
        SELECT r.role_name 
        FROM user_roles ur 
        JOIN roles r ON ur.role_id = r.role_id 
        WHERE ur.user_id = ? 
        LIMIT 1
    ");
    $roleStmt->execute([$userId]);
    $roleName = $roleStmt->fetchColumn();
    
    if ($roleName) {
        $roleCheck['details']['role'] = $roleName;
        $roleCheck['passed'] = (strtolower($roleName) === 'program head' || strtolower($roleName) === 'admin');
        if (!$roleCheck['passed']) {
            $roleCheck['details']['error'] = "User has role '$roleName' but needs 'Program Head' or 'Admin'";
        }
    } else {
        $roleCheck['details']['error'] = 'No role found for user';
    }
    $results['checks']['role'] = $roleCheck;

    // ============================================
    // CHECK 2: Program Head Designation
    // ============================================
    $designationCheck = [
        'name' => 'Program Head Designation Check',
        'passed' => false,
        'details' => []
    ];
    
    $designationStmt = $pdo->prepare("
        (SELECT s.designation_id, d.designation_name, 'staff' as source
         FROM staff s
         JOIN designations d ON s.designation_id = d.designation_id
         WHERE s.user_id = ? AND s.is_active = 1 AND d.is_active = 1 AND d.designation_name = 'Program Head')
        UNION
        (SELECT uda.designation_id, d.designation_name, 'user_designation_assignments' as source
         FROM user_designation_assignments uda
         JOIN designations d ON uda.designation_id = d.designation_id
         WHERE uda.user_id = ? AND uda.is_active = 1 AND d.is_active = 1 AND d.designation_name = 'Program Head')
    ");
    $designationStmt->execute([$userId, $userId]);
    $designations = $designationStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($designations)) {
        $designationCheck['passed'] = true;
        $designationCheck['details']['designations'] = $designations;
    } else {
        $designationCheck['details']['error'] = 'No Program Head designation found in staff or user_designation_assignments';
    }
    $results['checks']['designation'] = $designationCheck;

    // ============================================
    // CHECK 3: Department Assignments
    // ============================================
    $deptCheck = [
        'name' => 'Department Assignments Check',
        'passed' => false,
        'details' => []
    ];
    
    $deptStmt = $pdo->prepare("
        SELECT 
            uda.department_assignment_id,
            uda.department_id,
            uda.is_active,
            d.department_name,
            s.sector_name
        FROM user_department_assignments uda
        JOIN departments d ON uda.department_id = d.department_id
        LEFT JOIN sectors s ON d.sector_id = s.sector_id
        WHERE uda.user_id = ? AND uda.is_active = 1
        ORDER BY d.department_name
    ");
    $deptStmt->execute([$userId]);
    $departments = $deptStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($departments)) {
        $deptCheck['passed'] = true;
        $deptCheck['details']['count'] = count($departments);
        $deptCheck['details']['departments'] = $departments;
        $deptCheck['details']['department_ids'] = array_column($departments, 'department_id');
    } else {
        $deptCheck['details']['error'] = 'No active department assignments found in user_department_assignments';
        $deptCheck['details']['sql_fix'] = "INSERT INTO user_department_assignments (user_id, department_id, is_active) VALUES (?, ?, 1)";
    }
    $results['checks']['departments'] = $deptCheck;

    // ============================================
    // CHECK 4: Students/Faculty in Assigned Departments
    // ============================================
    $dataCheck = [
        'name' => ($type === 'faculty' ? 'Faculty' : 'Students') . ' in Assigned Departments',
        'passed' => false,
        'details' => []
    ];
    
    if (!empty($departments)) {
        $deptIds = array_column($departments, 'department_id');
        $deptPlaceholders = implode(',', array_fill(0, count($deptIds), '?'));
        
        if ($type === 'faculty') {
            $dataStmt = $pdo->prepare("
                SELECT 
                    COUNT(DISTINCT u.user_id) as total_count,
                    COUNT(DISTINCT uda.department_id) as departments_with_faculty
                FROM users u
                JOIN faculty f ON u.user_id = f.user_id
                JOIN user_department_assignments uda ON u.user_id = uda.user_id
                WHERE uda.department_id IN ($deptPlaceholders)
                AND uda.is_active = 1
            ");
        } else {
            $dataStmt = $pdo->prepare("
                SELECT 
                    COUNT(DISTINCT s.student_id) as total_count,
                    COUNT(DISTINCT s.department_id) as departments_with_students
                FROM students s
                WHERE s.department_id IN ($deptPlaceholders)
            ");
        }
        $dataStmt->execute($deptIds);
        $dataResult = $dataStmt->fetch(PDO::FETCH_ASSOC);
        
        $dataCheck['details'] = $dataResult;
        $dataCheck['passed'] = ((int)$dataResult['total_count']) > 0;
        
        if (!$dataCheck['passed']) {
            $dataCheck['details']['error'] = "No " . ($type === 'faculty' ? 'faculty' : 'students') . " found in assigned departments";
        }
    } else {
        $dataCheck['details']['error'] = 'Cannot check: No department assignments found';
    }
    $results['checks']['data_exists'] = $dataCheck;

    // ============================================
    // CHECK 5: Clearance Period / School Term
    // ============================================
    $periodCheck = [
        'name' => 'Clearance Period / School Term Check',
        'passed' => false,
        'details' => []
    ];
    
    $selectedAcademicYearId = null;
    $selectedSemesterId = null;
    
    if (!empty($schoolTerm)) {
        $termParts = explode('|', $schoolTerm);
        $yearName = $termParts[0] ?? '';
        $semesterId = $termParts[1] ?? 0;
        
        $ayStmt = $pdo->prepare("SELECT academic_year_id FROM academic_years WHERE year = ? LIMIT 1");
        $ayStmt->execute([$yearName]);
        $selectedAcademicYearId = $ayStmt->fetchColumn();
        $selectedSemesterId = (int)$semesterId;
        
        $periodCheck['details']['school_term'] = $schoolTerm;
        $periodCheck['details']['academic_year_id'] = $selectedAcademicYearId;
        $periodCheck['details']['semester_id'] = $selectedSemesterId;
        
        if ($selectedAcademicYearId && $selectedSemesterId) {
            // Check if clearance_period exists
            $periodStmt = $pdo->prepare("
                SELECT cp.*, ay.year as academic_year, sem.semester_name
                FROM clearance_periods cp
                JOIN academic_years ay ON cp.academic_year_id = ay.academic_year_id
                JOIN semesters sem ON cp.semester_id = sem.semester_id
                WHERE cp.academic_year_id = ? AND cp.semester_id = ?
                LIMIT 1
            ");
            $periodStmt->execute([$selectedAcademicYearId, $selectedSemesterId]);
            $period = $periodStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($period) {
                $periodCheck['passed'] = true;
                $periodCheck['details']['clearance_period'] = $period;
            } else {
                $periodCheck['details']['note'] = 'No clearance_period found, but API can query directly by academic_year_id and semester_id';
                $periodCheck['passed'] = true; // This is OK for historical data
            }
        }
    } else {
        // Check for active periods - filter by sector (Student or Faculty) and use for subsequent checks
        $sectorFilter = ($type === 'faculty') ? 'Faculty' : 'Student';
        $activePeriodStmt = $pdo->prepare("
            SELECT cp.*, ay.year as academic_year, sem.semester_name
            FROM clearance_periods cp
            JOIN academic_years ay ON cp.academic_year_id = ay.academic_year_id
            JOIN semesters sem ON cp.semester_id = sem.semester_id
            WHERE cp.status IN ('Ongoing', 'Closed')
            AND cp.sector = ?
            ORDER BY cp.period_id DESC
            LIMIT 1
        ");
        $activePeriodStmt->execute([$sectorFilter]);
        $activePeriod = $activePeriodStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($activePeriod) {
            $periodCheck['passed'] = true;
            $periodCheck['details']['active_period'] = $activePeriod;
            $periodCheck['details']['sector_filter'] = $sectorFilter;
            // Use active period's academic_year_id and semester_id for subsequent checks
            $selectedAcademicYearId = $activePeriod['academic_year_id'];
            $selectedSemesterId = $activePeriod['semester_id'];
            $periodCheck['details']['note'] = "Using active period for {$sectorFilter} sector (will be used for clearance forms/signatories checks)";
        } else {
            $periodCheck['details']['error'] = "No active clearance period found for {$sectorFilter} sector and no school_term specified";
            $periodCheck['details']['sector_filter'] = $sectorFilter;
        }
    }
    $results['checks']['period'] = $periodCheck;

    // ============================================
    // CHECK 6: Clearance Forms for Selected Term
    // ============================================
    $formsCheck = [
        'name' => 'Clearance Forms Check',
        'passed' => false,
        'details' => []
    ];
    
    if ($selectedAcademicYearId && $selectedSemesterId && !empty($departments)) {
        $deptIds = array_column($departments, 'department_id');
        $deptPlaceholders = implode(',', array_fill(0, count($deptIds), '?'));
        
        if ($type === 'faculty') {
            $formsStmt = $pdo->prepare("
                SELECT COUNT(DISTINCT cf.clearance_form_id) as form_count
                FROM clearance_forms cf
                JOIN faculty f ON cf.user_id = f.user_id
                JOIN user_department_assignments uda ON f.user_id = uda.user_id
                WHERE cf.academic_year_id = ? 
                AND cf.semester_id = ?
                AND uda.department_id IN ($deptPlaceholders)
                AND uda.is_active = 1
            ");
        } else {
            $formsStmt = $pdo->prepare("
                SELECT COUNT(DISTINCT cf.clearance_form_id) as form_count
                FROM clearance_forms cf
                JOIN students s ON cf.user_id = s.user_id
                WHERE cf.academic_year_id = ? 
                AND cf.semester_id = ?
                AND s.department_id IN ($deptPlaceholders)
            ");
        }
        
        $params = array_merge([$selectedAcademicYearId, $selectedSemesterId], $deptIds);
        $formsStmt->execute($params);
        $formCount = (int)$formsStmt->fetchColumn();
        
        $formsCheck['details']['form_count'] = $formCount;
        $formsCheck['details']['academic_year_id'] = $selectedAcademicYearId;
        $formsCheck['details']['semester_id'] = $selectedSemesterId;
        $formsCheck['passed'] = $formCount > 0;
        
        if (!$formsCheck['passed']) {
            $formsCheck['details']['error'] = "No clearance forms found for selected term";
        }
    } else {
        if (!$selectedAcademicYearId || !$selectedSemesterId) {
            $formsCheck['details']['error'] = 'Cannot check: No school term selected';
        } else {
            $formsCheck['details']['error'] = 'Cannot check: No department assignments found';
        }
    }
    $results['checks']['clearance_forms'] = $formsCheck;

    // ============================================
    // CHECK 7: Clearance Signatories with Program Head Designation
    // ============================================
    $signatoryCheck = [
        'name' => 'Clearance Signatories Check',
        'passed' => false,
        'details' => []
    ];
    
    if ($selectedAcademicYearId && $selectedSemesterId && !empty($departments)) {
        $deptIds = array_column($departments, 'department_id');
        $deptPlaceholders = implode(',', array_fill(0, count($deptIds), '?'));
        
        // Get Program Head designation_id
        $phDesignationStmt = $pdo->prepare("
            SELECT designation_id FROM designations WHERE designation_name = 'Program Head' LIMIT 1
        ");
        $phDesignationStmt->execute();
        $phDesignationId = $phDesignationStmt->fetchColumn();
        
        if ($phDesignationId) {
            if ($type === 'faculty') {
                $signatoryStmt = $pdo->prepare("
                    SELECT COUNT(DISTINCT cs.signatory_id) as signatory_count
                    FROM clearance_signatories cs
                    JOIN clearance_forms cf ON cs.clearance_form_id = cf.clearance_form_id
                    JOIN faculty f ON cf.user_id = f.user_id
                    JOIN user_department_assignments uda ON f.user_id = uda.user_id
                    WHERE cs.designation_id = ?
                    AND cf.academic_year_id = ?
                    AND cf.semester_id = ?
                    AND uda.department_id IN ($deptPlaceholders)
                    AND uda.is_active = 1
                ");
            } else {
                $signatoryStmt = $pdo->prepare("
                    SELECT COUNT(DISTINCT cs.signatory_id) as signatory_count
                    FROM clearance_signatories cs
                    JOIN clearance_forms cf ON cs.clearance_form_id = cf.clearance_form_id
                    JOIN students s ON cf.user_id = s.user_id
                    WHERE cs.designation_id = ?
                    AND cf.academic_year_id = ?
                    AND cf.semester_id = ?
                    AND s.department_id IN ($deptPlaceholders)
                ");
            }
            
            $params = array_merge([$phDesignationId, $selectedAcademicYearId, $selectedSemesterId], $deptIds);
            $signatoryStmt->execute($params);
            $signatoryCount = (int)$signatoryStmt->fetchColumn();
            
            $signatoryCheck['details']['signatory_count'] = $signatoryCount;
            $signatoryCheck['details']['program_head_designation_id'] = $phDesignationId;
            $signatoryCheck['passed'] = $signatoryCount > 0;
            
            if (!$signatoryCheck['passed']) {
                $signatoryCheck['details']['error'] = "No clearance_signatories found with Program Head designation";
                $signatoryCheck['details']['note'] = "This might be OK if clearance forms haven't been assigned signatories yet";
            }
        } else {
            $signatoryCheck['details']['error'] = 'Program Head designation not found in designations table';
        }
    } else {
        $signatoryCheck['details']['error'] = 'Cannot check: Missing required parameters';
    }
    $results['checks']['signatories'] = $signatoryCheck;

    // ============================================
    // SUMMARY
    // ============================================
    $allChecks = [
        'role' => $roleCheck['passed'],
        'designation' => $designationCheck['passed'],
        'departments' => $deptCheck['passed'],
        'data_exists' => $dataCheck['passed'],
        'period' => $periodCheck['passed'],
        'clearance_forms' => $formsCheck['passed'],
        'signatories' => $signatoryCheck['passed']
    ];
    
    $passedCount = count(array_filter($allChecks));
    $totalCount = count($allChecks);
    
    $results['summary'] = [
        'total_checks' => $totalCount,
        'passed_checks' => $passedCount,
        'failed_checks' => $totalCount - $passedCount,
        'all_passed' => $passedCount === $totalCount,
        'critical_failures' => []
    ];
    
    // Identify critical failures (ones that definitely prevent data from showing)
    if (!$deptCheck['passed']) {
        $results['summary']['critical_failures'][] = 'No department assignments - data cannot be scoped';
    }
    if (!$dataCheck['passed']) {
        $results['summary']['critical_failures'][] = 'No ' . ($type === 'faculty' ? 'faculty' : 'students') . ' in assigned departments';
    }
    if (!$formsCheck['passed'] && $selectedAcademicYearId && $selectedSemesterId) {
        $results['summary']['critical_failures'][] = 'No clearance forms for selected term';
    }
    
    // Determine if data should show
    $shouldShowData = $deptCheck['passed'] && $dataCheck['passed'] && 
                     ($formsCheck['passed'] || !$selectedAcademicYearId || !$selectedSemesterId);
    
    $results['summary']['data_should_show'] = $shouldShowData;
    
    if (!$shouldShowData) {
        $results['summary']['recommendation'] = 'Fix the critical failures listed above to enable data display';
    } else {
        $results['summary']['recommendation'] = 'All critical checks passed. If data still not showing, check API logs and browser console for errors.';
    }
    
    echo json_encode($results, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error running diagnostics',
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>

