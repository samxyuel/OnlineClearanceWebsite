<?php
/**
 * API: List Students for Signatory
 *
 * Fetches a list of students (both College and Senior High) for a logged-in staff signatory.
 * It determines the students based on the staff's assigned designation and the active clearance period.
 * Supports filtering, searching, and pagination.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/config/database.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

try {
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required.']);
        exit;
    }

    $userId = $auth->getUserId();
    $pdo = Database::getInstance()->getConnection();

    // 1. Get all of the staff member's active designation IDs and names from both staff table and assignments table.
    $staffStmt = $pdo->prepare("
        (SELECT s.designation_id, d.designation_name
         FROM staff s
         JOIN designations d ON s.designation_id = d.designation_id
         WHERE s.user_id = ? AND s.is_active = 1 AND d.is_active = 1 AND s.designation_id IS NOT NULL)
        UNION
        (SELECT uda.designation_id, d.designation_name
         FROM user_designation_assignments uda
         JOIN designations d ON uda.designation_id = d.designation_id
         WHERE uda.user_id = ? AND uda.is_active = 1 AND d.is_active = 1)
    ");
    $staffStmt->execute([$userId, $userId]);
    $staffDesignations = $staffStmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($staffDesignations)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access Denied: You are not an active staff member with any assigned designations.']);
        exit;
    }

    $designationIds = array_column($staffDesignations, 'designation_id');
    $designationNames = array_column($staffDesignations, 'designation_name');
    
    $isProgramHead = false;
    foreach ($designationNames as $name) {
        if (strcasecmp($name, 'Program Head') === 0) {
            $isProgramHead = true;
            break;
        }
    }
    $programHeadDepartments = [];
    $isShsProgramHead = false;

    if ($isProgramHead) {
        // For Program Heads, get their assigned department IDs to enforce scope
        $deptStmt = $pdo->prepare("SELECT department_id FROM user_department_assignments WHERE user_id = ?");
        $deptStmt->execute([$userId]);
        $programHeadDepartments = $deptStmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // 2. Get the active clearance period for the relevant sector
    $type = $_GET['type'] ?? 'student'; // 'student' or 'faculty'
    $sector = ($type === 'faculty') ? 'Faculty' : 'Student'; // Simplified sector for query

    // 3. Get filter and pagination parameters
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 20;
    $offset = ($page - 1) * $limit;
    $search = trim($_GET['search'] ?? '');
    $clearanceStatus = $_GET['clearance_status'] ?? '';
    $accountStatus = $_GET['account_status'] ?? '';
    $requestSector = $_GET['sector'] ?? ''; // 'College' or 'Senior High School'
    $schoolTerm = $_GET['school_term'] ?? ''; // e.g., "2024-2025|2"
    $programId = $_GET['program_id'] ?? '';
    $yearLevel = $_GET['year_level'] ?? '';
    $employmentStatus = $_GET['employment_status'] ?? ''; // New filter for faculty
    $designationFilter = trim($_GET['designation_filter'] ?? ''); // New filter for role switching
    // Handle single or multiple department IDs from frontend filter
    $departmentIds = $_GET['department_ids'] ?? null;
    $deptIds = [];
    if (!empty($departmentIds)) {
        $deptIds = explode(',', $departmentIds);
    }

    // Debug log for received department_ids
    error_log("SIGNATORY_LIST_DEBUG: Received department_ids = " . json_encode($departmentIds));


    // If a school term is selected, look for periods within that term (Ongoing or Closed)
    // Otherwise, default to the current 'Ongoing' period.
    $selectedAcademicYearId = null;
    $selectedSemesterId = null;
    
    if (!empty($schoolTerm)) {
        $termParts = explode('|', $schoolTerm);
        $yearName = $termParts[0] ?? '';
        $semesterId = $termParts[1] ?? 0;

        // Get academic_year_id for the selected term
        $ayStmt = $pdo->prepare("SELECT academic_year_id FROM academic_years WHERE year = :yearName LIMIT 1");
        $ayStmt->execute([':yearName' => $yearName]);
        $selectedAcademicYearId = $ayStmt->fetchColumn();
        $selectedSemesterId = (int)$semesterId;

        $periodQuery = "SELECT cp.period_id FROM clearance_periods cp JOIN academic_years ay ON cp.academic_year_id = ay.academic_year_id WHERE ay.year = :yearName AND cp.semester_id = :semesterId AND cp.status IN ('Ongoing', 'Closed')";
        $periodParams = [':yearName' => $yearName, ':semesterId' => $semesterId];
    } else {
        // If no term is specified, find the most relevant period based on status priority.
        $periodQuery = "
            SELECT period_id FROM (
                SELECT period_id,
                       CASE status
                           WHEN 'Ongoing' THEN 1
                           WHEN 'Paused' THEN 2
                           WHEN 'Closed' THEN 3
                           ELSE 4
                       END as status_priority
                FROM clearance_periods
                WHERE status IN ('Ongoing', 'Paused', 'Closed')
            ) as prioritized_periods
            ORDER BY status_priority, period_id DESC
            LIMIT 1";
        $periodParams = [];
    }

    $activePeriodsStmt = $pdo->prepare($periodQuery);
    $activePeriodsStmt->execute($periodParams);
    $activePeriodId = $activePeriodsStmt->fetchColumn();

    if (!$activePeriodId && empty($schoolTerm)) {
        // Return an empty list if no period is active, which is not an error.
        $responseKey = ($type === 'faculty') ? 'faculty' : 'students';
        echo json_encode(['success' => true, 'total' => 0, $responseKey => [], 'page' => 1, 'limit' => 10, 'stats' => ['total' => 0, 'active' => 0, 'inactive' => 0, 'graduated' => 0]]);
        exit;
    }
    
    // Flag to indicate if we should query clearance_forms directly (when schoolTerm provided but no period_id found)
    $queryDirectByTerm = (!empty($schoolTerm) && !$activePeriodId && $selectedAcademicYearId && $selectedSemesterId);

    // 4. Build the query based on type
    $params = [];
    $designationPlaceholders = [];
    foreach ($designationIds as $i => $id) {
        $key = ":designationId_$i";
        $designationPlaceholders[] = $key;
        $params[$key] = $id;
    }
    $designationInClause = implode(',', $designationPlaceholders);

    // Build the clearance_periods JOIN condition based on whether we have a specific period_id
    // When $activePeriodId is set (from school_term filter), use it to ensure we query the correct period
    // When schoolTerm is provided but no period_id found, query clearance_forms directly by academic_year_id and semester_id
    if ($queryDirectByTerm) {
        // Query clearance_forms directly by academic_year_id and semester_id (no clearance_period required)
        $periodJoinCondition = "1=1"; // Dummy condition, won't be used
    } else if ($activePeriodId) {
        // Use the specific period_id when available (from school_term filter)
        $periodJoinCondition = "cp.period_id = :activePeriodId";
        $params[':activePeriodId'] = $activePeriodId;
    } else {
        // Fallback to status-based matching when no specific period is selected
        $periodJoinCondition = "cp.status IN ('Ongoing', 'Closed')";
    }

    if (strtolower($type) === 'faculty') {
        $select = "
            SELECT SQL_CALC_FOUND_ROWS
                f.employee_number as id,
                u.user_id,
                u.first_name,
                u.last_name,
                GROUP_CONCAT(DISTINCT d.department_name SEPARATOR ', ') as departments,
                f.employment_status,
                f.employment_status as year_level,
                '' as section, -- Faculty don't have sections
                u.account_status as account_status,
                cs.action as clearance_status,
                cs.signatory_id,
                cf.clearance_form_id,
                cf.clearance_form_progress,
                CONCAT(ay.year, ' ', sem.semester_name) as school_term,
                d_sig.designation_name as required_designation
        ";
        
        if ($queryDirectByTerm) {
            // Query clearance_forms directly by academic_year_id and semester_id (no clearance_period required)
            $from = "
                FROM faculty f
                JOIN users u ON f.user_id = u.user_id
                LEFT JOIN user_department_assignments uda ON u.user_id = uda.user_id
                LEFT JOIN departments d ON uda.department_id = d.department_id
                LEFT JOIN clearance_forms cf ON f.user_id = cf.user_id AND cf.academic_year_id = :selectedAcademicYearId AND cf.semester_id = :selectedSemesterId
                LEFT JOIN clearance_signatories cs ON cf.clearance_form_id = cs.clearance_form_id AND cs.designation_id IN ($designationInClause)
                LEFT JOIN designations d_sig ON cs.designation_id = d_sig.designation_id
                LEFT JOIN academic_years ay ON cf.academic_year_id = ay.academic_year_id
                LEFT JOIN semesters sem ON cf.semester_id = sem.semester_id
            ";
            $params[':selectedAcademicYearId'] = $selectedAcademicYearId;
            $params[':selectedSemesterId'] = $selectedSemesterId;
        } else {
            // Normal query with clearance_periods
            $from = "
                FROM faculty f
                JOIN users u ON f.user_id = u.user_id
                LEFT JOIN user_department_assignments uda ON u.user_id = uda.user_id
                LEFT JOIN departments d ON uda.department_id = d.department_id
                LEFT JOIN clearance_periods cp ON cp.sector = 'Faculty' AND ($periodJoinCondition)
                LEFT JOIN clearance_forms cf ON f.user_id = cf.user_id AND cf.academic_year_id = cp.academic_year_id AND cf.semester_id = cp.semester_id 
                LEFT JOIN clearance_signatories cs ON cf.clearance_form_id = cs.clearance_form_id AND cs.designation_id IN ($designationInClause)
                LEFT JOIN designations d_sig ON cs.designation_id = d_sig.designation_id
                LEFT JOIN academic_years ay ON cf.academic_year_id = ay.academic_year_id
                LEFT JOIN semesters sem ON cf.semester_id = sem.semester_id
            ";
        }
        $searchFields = ['u.first_name', 'u.last_name', 'f.employee_number', 'd.department_name', 'f.employment_status', 'd_sig.designation_name'];
    } else { // Default to student
        $select = "
            SELECT SQL_CALC_FOUND_ROWS
                s.student_id as id,
                u.user_id,
                u.first_name,
                u.last_name,
                p.program_code as program,
                s.year_level,
                s.section,
                u.account_status as account_status,
                cs.action as clearance_status,
                cs.signatory_id,
                cf.clearance_form_id,
                cf.clearance_form_progress,
                d_sig.designation_name as required_designation
        ";
        
        if ($queryDirectByTerm) {
            // Query clearance_forms directly by academic_year_id and semester_id (no clearance_period required)
            $from = "
                FROM students s
                JOIN users u ON s.user_id = u.user_id
                LEFT JOIN programs p ON s.program_id = p.program_id
                LEFT JOIN departments d ON s.department_id = d.department_id
                LEFT JOIN clearance_forms cf ON s.user_id = cf.user_id AND cf.academic_year_id = :selectedAcademicYearId AND cf.semester_id = :selectedSemesterId
                LEFT JOIN clearance_signatories cs ON cf.clearance_form_id = cs.clearance_form_id AND cs.designation_id IN ($designationInClause)
                LEFT JOIN designations d_sig ON cs.designation_id = d_sig.designation_id
            ";
            // Add params if not already added (for faculty case)
            if (!isset($params[':selectedAcademicYearId'])) {
                $params[':selectedAcademicYearId'] = $selectedAcademicYearId;
                $params[':selectedSemesterId'] = $selectedSemesterId;
            }
        } else {
            // Normal query with clearance_periods
            $from = "
                FROM students s
                JOIN users u ON s.user_id = u.user_id
                LEFT JOIN programs p ON s.program_id = p.program_id
                LEFT JOIN departments d ON s.department_id = d.department_id
                LEFT JOIN clearance_periods cp ON cp.sector = s.sector AND ($periodJoinCondition)
                LEFT JOIN clearance_forms cf ON s.user_id = cf.user_id AND cf.academic_year_id = cp.academic_year_id AND cf.semester_id = cp.semester_id 
                LEFT JOIN clearance_signatories cs ON cf.clearance_form_id = cs.clearance_form_id AND cs.designation_id IN ($designationInClause)
                LEFT JOIN designations d_sig ON cs.designation_id = d_sig.designation_id
            ";
        }
        $searchFields = ['u.first_name', 'u.last_name', 's.student_id', 'p.program_code', 'year_level', 'd_sig.designation_name'];
    }

    $where = " WHERE 1=1"; 
    // $params are already populated with designation IDs
    
    // Apply designation filter if provided (for role switching)
    if (!empty($designationFilter)) {
        $where .= " AND d_sig.designation_name = :designationFilter";
        $params[':designationFilter'] = $designationFilter;
    }

    // SERVER-SIDE SCOPING for Program Heads
    if ($isProgramHead && !empty($programHeadDepartments)) {
        $phDeptPlaceholders = [];
        foreach ($programHeadDepartments as $i => $id) {
            $key = ":ph_dept_id_$i";
            $phDeptPlaceholders[] = $key;
            $params[$key] = $id;
        }
        $phInClause = implode(',', $phDeptPlaceholders);

        if (strtolower($type) === 'faculty') {
            // This ensures we only get faculty that share at least one department with the Program Head
            $where .= " AND u.user_id IN (SELECT user_id FROM user_department_assignments WHERE department_id IN ($phInClause))";
        } else {
            // For students, we check their assigned department directly
            $where .= " AND s.department_id IN ($phInClause)";
        }
    }

    // Apply department filtering from the frontend (optional, for further filtering)
    if (!empty($deptIds)) {
        $deptPlaceholders = [];
        foreach ($deptIds as $i => $id) {
            $key = ":dept_id_$i";
            $deptPlaceholders[] = $key;
            $params[$key] = $id;
        }
        $inClause = implode(',', $deptPlaceholders);
        if (strtolower($type) === 'faculty') {
            // The main scoping is already done. This is an additional filter.
            $where .= " AND u.user_id IN (SELECT user_id FROM user_department_assignments WHERE department_id IN ($inClause))";
        } else {
            $where .= " AND s.department_id IN ($inClause)";
        }
    }

    // Apply filters
    if (!empty($search)) {
        $searchTerms = explode(' ', $search);
        $searchClauses = [];
        $termIndex = 0;

        // Special handling for full name search
        $searchClauses[] = "CONCAT(u.first_name, ' ', u.last_name) LIKE :searchFullName";
        $params[':searchFullName'] = "%$search%";

        // Add individual field searches
        foreach ($searchFields as $field) {
            // Avoid duplicating name search
            if ($field !== 'u.first_name' && $field !== 'u.last_name') {
                $searchClauses[] = "$field LIKE :search" . $termIndex;
                $params[":search" . $termIndex] = "%$search%";
                $termIndex++;
            }
        }
        $where .= " AND (" . implode(' OR ', $searchClauses) . ")";
    }

    if (!empty($clearanceStatus) && $clearanceStatus !== 'all') {
        $where .= " AND COALESCE(cs.action, 'Unapplied') = :clearanceStatus";
        $params[':clearanceStatus'] = $clearanceStatus;
    } else {
        // By default, show actionable items (not 'Unapplied')
        // This logic can be adjusted based on requirements. For now, showing all.
        // $where .= " AND COALESCE(cs.action, 'Unapplied') != 'Unapplied'";
    }

    // If employment status filter is provided (for faculty)
    if (!empty($employmentStatus) && strtolower($type) === 'faculty') {
        $where .= " AND f.employment_status = :employmentStatus";
        $params[':employmentStatus'] = $employmentStatus;
    }

    if (!empty($accountStatus)) {
        $where .= " AND u.account_status = :accountStatus";
        $params[':accountStatus'] = $accountStatus;
    }

    // Note: school_term filtering is handled by:
    // 1. $activePeriodId in the JOIN (when clearance_period exists)
    // 2. Direct filtering by academic_year_id and semester_id in the clearance_forms JOIN (when queryDirectByTerm is true)
    // No additional WHERE clause filtering needed

    if (!empty($programId)) {
        $where .= " AND p.program_id = :programId";
        $params[':programId'] = $programId;
    }

    if (!empty($yearLevel)) {
        $where .= " AND s.year_level = :yearLevel";
        $params[':yearLevel'] = $yearLevel;
    }

    // Add sector filtering for students
    if ($type === 'student' && !empty($requestSector)) {
        // Assuming 'students' table has a 'sector' column ('College' or 'Senior High School')
        $where .= " AND s.sector = :requestSector";
        $params[':requestSector'] = $requestSector;
    }
    
    // Conditional sorting
    $groupBy = "";
    if (strtolower($type) === 'faculty') {
        $groupBy = " GROUP BY f.employee_number";
    }
    $orderBy = " ORDER BY 
        CASE 
            WHEN cs.action = 'Pending' THEN 1
            WHEN cs.action = 'Rejected' THEN 2
            WHEN cs.action = 'Approved' THEN 3
            ELSE 4
        END,
        u.last_name, u.first_name";
    $limitClause = " LIMIT :limit OFFSET :offset";

    // Prepare and execute the main query
    // --- DEBUG LOGGING ---
    error_log("SIGNATORY_LIST_DEBUG: Final SQL Query: " . $select . $from . $where . $groupBy . $orderBy . $limitClause);
    error_log("SIGNATORY_LIST_DEBUG: Final Parameters: " . json_encode($params, JSON_PRETTY_PRINT));
    error_log("SIGNATORY_LIST_DEBUG: Limit: " . $limit . ", Offset: " . $offset);
    // --- END DEBUG LOGGING ---

    $stmt = $pdo->prepare($select . $from . $where . $groupBy . $orderBy . $limitClause);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    foreach ($params as $key => &$val) {
        $stmt->bindParam($key, $val);
    }
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total count for pagination
    $total = $pdo->query("SELECT FOUND_ROWS()")->fetchColumn();

    // 5. Get statistics
    if (strtolower($type) === 'faculty') {
        $statsQuery = "SELECT u.account_status, COUNT(*) as count FROM users u JOIN faculty f ON u.user_id = f.user_id GROUP BY u.account_status";
        $statsStmt = $pdo->prepare($statsQuery);
        $statsStmt->execute();
    } else { // student
        $statsQuery = "SELECT u.account_status, COUNT(*) as count FROM users u JOIN students s ON u.user_id = s.user_id WHERE s.sector = :requestSector GROUP BY u.account_status";
        $statsStmt = $pdo->prepare($statsQuery);
        $statsStmt->execute([':requestSector' => $requestSector]);
    }
    $statsResults = $statsStmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $stats = [
        'total' => array_sum($statsResults),
        'active' => $statsResults['active'] ?? 0,
        'inactive' => $statsResults['inactive'] ?? 0,
        'graduated' => $statsResults['graduated'] ?? 0, // For students
        'resigned' => $statsResults['resigned'] ?? 0, // For faculty
    ];


    // 5. Format and return the response
    $responseKey = ($type === 'faculty') ? 'faculty' : 'students';
    $response = [
        'success' => true,
        'total' => (int)$total,
        'page' => $page,
        'limit' => $limit,
        'stats' => $stats,
        $responseKey => array_map(function ($item) use ($type) {
            // Map clearance_form_progress to proper format (unapplied -> Unapplied, in-progress -> In Progress, complete -> Completed)
            $progress = $item['clearance_form_progress'] ?? 'unapplied';
            $progressMap = [
                'unapplied' => 'Unapplied',
                'in-progress' => 'In Progress',
                'complete' => 'Completed',
                'rejected' => 'Rejected'
            ];
            $clearanceFormProgress = $progressMap[strtolower($progress)] ?? ucfirst(str_replace('-', ' ', $progress));
            
            $mappedItem = [
                'id' => $item['id'],
                'user_id' => $item['user_id'],
                'name' => trim($item['first_name'] . ' ' . $item['last_name']),
                'year_level' => $item['year_level'],
                'section' => $item['section'],
                'account_status' => $item['account_status'],
                'clearance_status' => $item['clearance_status'] ?? 'Unapplied',
                'clearance_form_progress' => $clearanceFormProgress,
                'clearance_form_id' => $item['clearance_form_id'],
                'signatory_id' => $item['signatory_id'],
                'required_designation' => $item['required_designation']
            ];

            if (strtolower($type) === 'faculty') {
                $mappedItem['departments'] = $item['departments'];
                $mappedItem['employment_status'] = $item['employment_status'] ?? null;
                $mappedItem['school_term'] = $item['school_term'];
            } else {
                $mappedItem['program'] = $item['program'];
            }

            return $mappedItem;
        }, $results),
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server Error: ' . $e->getMessage()
    ]);
}
?>