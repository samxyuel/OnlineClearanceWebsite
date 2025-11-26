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
    $userRole = $auth->getRoleName(); // Get user's role
    $isSchoolAdmin = ($userRole === 'School Administrator');
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
    $type = isset($_GET['type']) ? strtolower(trim($_GET['type'])) : 'student'; // 'student' or 'faculty'
    // Ensure type is valid
    if ($type !== 'faculty' && $type !== 'student') {
        $type = 'student'; // Default to student if invalid
    }
    $sector = ($type === 'faculty') ? 'Faculty' : 'Student'; // Simplified sector for query
    
    // Debug logging
    error_log("SIGNATORY_LIST_DEBUG: Type parameter = " . $type . " (raw: " . ($_GET['type'] ?? 'not set') . ")");

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
    
    // Determine the correct sector to filter periods by
    // This ensures we get the right period for the right sector (College, Senior High School, or Faculty)
    $periodSector = '';
    if (strtolower($type) === 'faculty') {
        $periodSector = 'Faculty';
    } else if (!empty($requestSector)) {
        // For students, use the requestSector directly ('College' or 'Senior High School')
        $periodSector = $requestSector;
    } else {
        // Fallback: if no requestSector provided, default to 'College' (for backward compatibility with CollegeStudentManagement)
        $periodSector = 'College';
    }
    
    if (!empty($schoolTerm)) {
        $termParts = explode('|', $schoolTerm);
        $yearName = $termParts[0] ?? '';
        $semesterId = $termParts[1] ?? 0;

        // Get academic_year_id for the selected term
        $ayStmt = $pdo->prepare("SELECT academic_year_id FROM academic_years WHERE year = :yearName LIMIT 1");
        $ayStmt->execute([':yearName' => $yearName]);
        $selectedAcademicYearId = $ayStmt->fetchColumn();
        $selectedSemesterId = (int)$semesterId;

        // Filter period query by sector to ensure we get the correct period for the requested sector
        $periodQuery = "SELECT cp.period_id FROM clearance_periods cp 
            JOIN academic_years ay ON cp.academic_year_id = ay.academic_year_id 
            WHERE ay.year = :yearName 
            AND cp.semester_id = :semesterId 
            AND cp.status IN ('Not Started', 'Ongoing', 'Paused', 'Closed')
            AND cp.sector = :periodSector";
        $periodParams = [':yearName' => $yearName, ':semesterId' => $semesterId, ':periodSector' => $periodSector];
    } else {
        // If no term is specified, find the most relevant period based on status priority.
        // Filter by sector to ensure we get the correct period for the requested sector
        $periodQuery = "
            SELECT period_id FROM (
                SELECT period_id,
                       CASE status
                           WHEN 'Ongoing' THEN 1
                           WHEN 'Paused' THEN 2
                           WHEN 'Not Started' THEN 3
                           WHEN 'Closed' THEN 4
                           ELSE 5
                       END as status_priority
                FROM clearance_periods
                WHERE status IN ('Not Started', 'Ongoing', 'Paused', 'Closed')
                AND sector = :periodSector
            ) as prioritized_periods
            ORDER BY status_priority, period_id DESC
            LIMIT 1";
        $periodParams = [':periodSector' => $periodSector];
    }

    $activePeriodsStmt = $pdo->prepare($periodQuery);
    $activePeriodsStmt->execute($periodParams);
    $activePeriodId = $activePeriodsStmt->fetchColumn();

    // if (!$activePeriodId && empty($schoolTerm)) {
    //     // Return an empty list if no period is active, which is not an error.
    //     $responseKey = ($type === 'faculty') ? 'faculty' : 'students';
    //     echo json_encode(['success' => true, 'total' => 0, $responseKey => [], 'page' => 1, 'limit' => 10, 'stats' => ['total' => 0, 'active' => 0, 'inactive' => 0, 'graduated' => 0]]);
    //     exit;
    // }
    
    // Flag to indicate if we should query clearance_forms directly (when schoolTerm provided but no period_id found)
    $queryDirectByTerm = (!empty($schoolTerm) && !$activePeriodId && $selectedAcademicYearId && $selectedSemesterId);

    // ========== COMPREHENSIVE DEBUG LOGGING ==========
    error_log("SIGNATORY_LIST_DEBUG: ========== START REQUEST ==========");
    error_log("SIGNATORY_LIST_DEBUG: User ID: " . $userId);
    error_log("SIGNATORY_LIST_DEBUG: User Role: " . $userRole);
    error_log("SIGNATORY_LIST_DEBUG: Is School Admin: " . ($isSchoolAdmin ? 'true' : 'false'));
    error_log("SIGNATORY_LIST_DEBUG: Type: " . $type);
    error_log("SIGNATORY_LIST_DEBUG: Request Sector: " . ($requestSector ?: 'none'));
    error_log("SIGNATORY_LIST_DEBUG: School Term: " . ($schoolTerm ?: 'none'));
    error_log("SIGNATORY_LIST_DEBUG: Designation IDs: " . json_encode($designationIds));
    error_log("SIGNATORY_LIST_DEBUG: Designation Names: " . json_encode($designationNames));
    error_log("SIGNATORY_LIST_DEBUG: Active Period ID: " . ($activePeriodId ?: 'null'));
    error_log("SIGNATORY_LIST_DEBUG: Selected Academic Year ID: " . ($selectedAcademicYearId ?: 'null'));
    error_log("SIGNATORY_LIST_DEBUG: Selected Semester ID: " . ($selectedSemesterId ?: 'null'));
    error_log("SIGNATORY_LIST_DEBUG: Query Direct By Term: " . ($queryDirectByTerm ? 'true' : 'false'));
    // ========== END INITIAL DEBUG LOGGING ==========

    // 4. Build the query based on type
    $params = [];
    $designationPlaceholders = [];
    if (!empty($designationIds)) {
        foreach ($designationIds as $i => $id) {
            $key = ":designationId_$i";
            $designationPlaceholders[] = $key;
            $params[$key] = $id;
        }
    }
    $designationInClause = !empty($designationPlaceholders) ? implode(',', $designationPlaceholders) : 'NULL';
    
    error_log("SIGNATORY_LIST_DEBUG: Designation In Clause: " . $designationInClause);
    error_log("SIGNATORY_LIST_DEBUG: Designation Placeholders: " . json_encode($designationPlaceholders));
    error_log("SIGNATORY_LIST_DEBUG: Designation IDs: " . json_encode($designationIds));
    error_log("SIGNATORY_LIST_DEBUG: Is School Admin: " . ($isSchoolAdmin ? 'true' : 'false'));
    error_log("SIGNATORY_LIST_DEBUG: Period Join Condition: " . ($periodJoinCondition ?? 'not set yet'));

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
        $periodJoinCondition = "cp.status IN ('Not Started', 'Ongoing', 'Paused', 'Closed')";
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
        
        // For School Administrators: show all applicants regardless of signatory assignment
        // For Regular Staff: show all applicants (view-only mode enabled), permission check determines if actions are allowed
        $signatoryJoinCondition = "cf.clearance_form_id = cs.clearance_form_id";
        
        if ($queryDirectByTerm) {
            // Query clearance_forms directly by academic_year_id and semester_id (no clearance_period required)
            $from = "
                FROM faculty f
                JOIN users u ON f.user_id = u.user_id
                LEFT JOIN user_department_assignments uda ON u.user_id = uda.user_id
                LEFT JOIN departments d ON uda.department_id = d.department_id
                LEFT JOIN clearance_forms cf ON f.user_id = cf.user_id AND cf.academic_year_id = :selectedAcademicYearId AND cf.semester_id = :selectedSemesterId
                LEFT JOIN clearance_signatories cs ON $signatoryJoinCondition
                LEFT JOIN designations d_sig ON cs.designation_id = d_sig.designation_id
                LEFT JOIN academic_years ay ON cf.academic_year_id = ay.academic_year_id
                LEFT JOIN semesters sem ON cf.semester_id = sem.semester_id
            ";
            $params[':selectedAcademicYearId'] = $selectedAcademicYearId;
            $params[':selectedSemesterId'] = $selectedSemesterId;
        } else if ($isSchoolAdmin && !$activePeriodId) {
            // For School Admins with no period: Show all applicants without clearance_period join
            // This enables view-only mode even when there are no clearance periods
            error_log("SIGNATORY_LIST_DEBUG: Using no-period query for School Admin (faculty)");
            $from = "
                FROM faculty f
                JOIN users u ON f.user_id = u.user_id
                LEFT JOIN user_department_assignments uda ON u.user_id = uda.user_id
                LEFT JOIN departments d ON uda.department_id = d.department_id
                LEFT JOIN clearance_forms cf ON f.user_id = cf.user_id
                LEFT JOIN clearance_signatories cs ON $signatoryJoinCondition
                LEFT JOIN designations d_sig ON cs.designation_id = d_sig.designation_id
                LEFT JOIN academic_years ay ON cf.academic_year_id = ay.academic_year_id
                LEFT JOIN semesters sem ON cf.semester_id = sem.semester_id
            ";
        } else if (!$isSchoolAdmin && !$activePeriodId && !$queryDirectByTerm) {
            // For Regular Staff with no period: Show all applicants without clearance_period join
            // This enables view-only mode even when there are no clearance periods
            error_log("SIGNATORY_LIST_DEBUG: Using no-period query for Regular Staff (faculty)");
            $from = "
                FROM faculty f
                JOIN users u ON f.user_id = u.user_id
                LEFT JOIN user_department_assignments uda ON u.user_id = uda.user_id
                LEFT JOIN departments d ON uda.department_id = d.department_id
                LEFT JOIN clearance_forms cf ON f.user_id = cf.user_id
                LEFT JOIN clearance_signatories cs ON $signatoryJoinCondition
                LEFT JOIN designations d_sig ON cs.designation_id = d_sig.designation_id
                LEFT JOIN academic_years ay ON cf.academic_year_id = ay.academic_year_id
                LEFT JOIN semesters sem ON cf.semester_id = sem.semester_id
            ";
        } else {
            // Normal query with clearance_periods
            $from = "
                FROM faculty f
                JOIN users u ON f.user_id = u.user_id
                LEFT JOIN user_department_assignments uda ON u.user_id = uda.user_id
                LEFT JOIN departments d ON uda.department_id = d.department_id
                LEFT JOIN clearance_periods cp ON cp.sector = 'Faculty' AND ($periodJoinCondition)
                LEFT JOIN clearance_forms cf ON f.user_id = cf.user_id AND cf.academic_year_id = cp.academic_year_id AND cf.semester_id = cp.semester_id 
                LEFT JOIN clearance_signatories cs ON $signatoryJoinCondition
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
        
        // For School Administrators: show all applicants regardless of signatory assignment
        // For Regular Staff: show all applicants (view-only mode enabled), permission check determines if actions are allowed
        $signatoryJoinCondition = "cf.clearance_form_id = cs.clearance_form_id";
        
        if ($queryDirectByTerm) {
            // Query clearance_forms directly by academic_year_id and semester_id (no clearance_period required)
            $from = "
                FROM students s
                JOIN users u ON s.user_id = u.user_id
                LEFT JOIN programs p ON s.program_id = p.program_id
                LEFT JOIN departments d ON s.department_id = d.department_id
                LEFT JOIN clearance_forms cf ON s.user_id = cf.user_id AND cf.academic_year_id = :selectedAcademicYearId AND cf.semester_id = :selectedSemesterId
                LEFT JOIN clearance_signatories cs ON $signatoryJoinCondition
                LEFT JOIN designations d_sig ON cs.designation_id = d_sig.designation_id
            ";
            // Add params if not already added (for faculty case)
            if (!isset($params[':selectedAcademicYearId'])) {
                $params[':selectedAcademicYearId'] = $selectedAcademicYearId;
                $params[':selectedSemesterId'] = $selectedSemesterId;
            }
        } else if ($isSchoolAdmin && !$activePeriodId) {
            // For School Admins with no period: Show all applicants without clearance_period join
            // This enables view-only mode even when there are no clearance periods
            error_log("SIGNATORY_LIST_DEBUG: Using no-period query for School Admin (student)");
            $from = "
                FROM students s
                JOIN users u ON s.user_id = u.user_id
                LEFT JOIN programs p ON s.program_id = p.program_id
                LEFT JOIN departments d ON s.department_id = d.department_id
                LEFT JOIN clearance_forms cf ON s.user_id = cf.user_id
                LEFT JOIN clearance_signatories cs ON $signatoryJoinCondition
                LEFT JOIN designations d_sig ON cs.designation_id = d_sig.designation_id
            ";
        } else if (!$isSchoolAdmin && !$activePeriodId && !$queryDirectByTerm) {
            // For Regular Staff with no period: Show all applicants without clearance_period join
            // This enables view-only mode even when there are no clearance periods
            error_log("SIGNATORY_LIST_DEBUG: Using no-period query for Regular Staff (student)");
            $from = "
                FROM students s
                JOIN users u ON s.user_id = u.user_id
                LEFT JOIN programs p ON s.program_id = p.program_id
                LEFT JOIN departments d ON s.department_id = d.department_id
                LEFT JOIN clearance_forms cf ON s.user_id = cf.user_id
                LEFT JOIN clearance_signatories cs ON $signatoryJoinCondition
                LEFT JOIN designations d_sig ON cs.designation_id = d_sig.designation_id
            ";
        } else {
            // Normal query with clearance_periods
            $from = "
                FROM students s
                JOIN users u ON s.user_id = u.user_id
                LEFT JOIN programs p ON s.program_id = p.program_id
                LEFT JOIN departments d ON s.department_id = d.department_id
                LEFT JOIN clearance_periods cp ON cp.sector = s.sector AND ($periodJoinCondition)
                LEFT JOIN clearance_forms cf ON s.user_id = cf.user_id AND cf.academic_year_id = cp.academic_year_id AND cf.semester_id = cp.semester_id 
                LEFT JOIN clearance_signatories cs ON $signatoryJoinCondition
                LEFT JOIN designations d_sig ON cs.designation_id = d_sig.designation_id
            ";
        }
        $searchFields = ['u.first_name', 'u.last_name', 's.student_id', 'p.program_code', 'year_level', 'd_sig.designation_name'];
    }

    $where = " WHERE 1=1"; 
    // $params are already populated with designation IDs
    
    // Apply designation filter if provided (for role switching)
    // For School Administrators: Don't filter by designation (show all)
    // For Regular Staff: Only filter if the designation_filter matches one of their actual designations
    //                    If it doesn't match, show all records in view-only mode
    if (!empty($designationFilter) && !$isSchoolAdmin) {
        // Check if the designation filter matches any of the staff's actual designations
        $filterMatchesStaffDesignation = false;
        if (!empty($staffDesignations)) {
            foreach ($staffDesignations as $desig) {
                if (strcasecmp($desig['designation_name'], $designationFilter) === 0) {
                    $filterMatchesStaffDesignation = true;
                    break;
                }
            }
        }
        
        // Only apply the WHERE filter if the designation matches
        // This allows "View Only" mode when the filter doesn't match (show all records)
        if ($filterMatchesStaffDesignation) {
            $where .= " AND (d_sig.designation_name = :designationFilter OR cf.clearance_form_id IS NULL)";
            $params[':designationFilter'] = $designationFilter;
            error_log("SIGNATORY_LIST_DEBUG: Designation filter matches staff designation - applying WHERE filter");
        } else {
            error_log("SIGNATORY_LIST_DEBUG: Designation filter does NOT match staff designations - showing all records (view-only mode)");
        }
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
    if ($type === 'faculty') {
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

    // Build SQL string for debugging
    $sqlString = $select . $from . $where . $groupBy . $orderBy . $limitClause;

    // --- DEBUG LOGGING BEFORE PARAMETER FILTERING ---
    error_log("SIGNATORY_LIST_DEBUG: ========== PARAMETER FILTERING DEBUG ==========");
    error_log("SIGNATORY_LIST_DEBUG: Is School Admin: " . ($isSchoolAdmin ? 'true' : 'false'));
    error_log("SIGNATORY_LIST_DEBUG: Total params before filtering: " . count($params));
    error_log("SIGNATORY_LIST_DEBUG: Params keys before filtering: " . implode(', ', array_keys($params)));

    // Check which designationId parameters exist
    $designationIdParams = array_filter(array_keys($params), function($key) {
        return strpos($key, ':designationId_') !== false;
    });
    error_log("SIGNATORY_LIST_DEBUG: DesignationId params found: " . (empty($designationIdParams) ? 'none' : implode(', ', $designationIdParams)));

    // Check if designationInClause is used in SQL
    $usesDesignationInClause = (strpos($sqlString, $designationInClause) !== false);
    error_log("SIGNATORY_LIST_DEBUG: SQL uses designationInClause: " . ($usesDesignationInClause ? 'YES' : 'NO'));
    error_log("SIGNATORY_LIST_DEBUG: DesignationInClause value: " . $designationInClause);
    error_log("SIGNATORY_LIST_DEBUG: ========== END PRE-FILTERING DEBUG ==========");
    // --- END DEBUG LOGGING BEFORE FILTERING ---

    // Prepare and execute the main query
    // --- DEBUG LOGGING ---
    error_log("SIGNATORY_LIST_DEBUG: ========== MAIN QUERY ==========");
    error_log("SIGNATORY_LIST_DEBUG: Signatory Join Condition: " . $signatoryJoinCondition);
    error_log("SIGNATORY_LIST_DEBUG: FROM clause (first 500 chars): " . substr($from, 0, 500));
    error_log("SIGNATORY_LIST_DEBUG: WHERE clause (first 500 chars): " . substr($where, 0, 500));
    error_log("SIGNATORY_LIST_DEBUG: Final SQL Query (first 2000 chars): " . substr($sqlString, 0, 2000));
    error_log("SIGNATORY_LIST_DEBUG: Final Parameters: " . json_encode($params, JSON_PRETTY_PRINT));
    error_log("SIGNATORY_LIST_DEBUG: Limit: " . $limit . ", Offset: " . $offset);
    
    // Count placeholders in SQL vs parameters provided
    preg_match_all('/:(\w+)/', $sqlString, $matches);
    $sqlPlaceholders = array_unique($matches[1]);
    $paramKeys = array_map(function($key) { return ltrim($key, ':'); }, array_keys($params));
    $missingParams = array_diff($sqlPlaceholders, $paramKeys);
    $extraParams = array_diff($paramKeys, $sqlPlaceholders);
    error_log("SIGNATORY_LIST_DEBUG: SQL Placeholders: " . implode(', ', $sqlPlaceholders));
    error_log("SIGNATORY_LIST_DEBUG: Provided Parameters: " . implode(', ', $paramKeys));
    if (!empty($missingParams)) {
        error_log("SIGNATORY_LIST_DEBUG: WARNING - Missing parameters in SQL: " . implode(', ', $missingParams));
    }
    if (!empty($extraParams)) {
        error_log("SIGNATORY_LIST_DEBUG: INFO - Extra parameters (not in SQL): " . implode(', ', $extraParams));
    }
    // --- END DEBUG LOGGING ---

    $stmt = $pdo->prepare($sqlString);
    
    // Use execute() with params array instead of bindParam loop
    // This is more reliable and handles duplicate parameter names correctly
    // PDO will automatically handle parameters that appear multiple times in the SQL
    // Remove unused designationId parameters since they're not in the SQL (for both School Admins and Regular Staff)
    // Both roles now use the same JOIN condition without designation filtering
    $executeParams = array_filter($params, function($key) {
        return strpos($key, ':designationId_') === false;
    }, ARRAY_FILTER_USE_KEY);
    $executeParams[':limit'] = (int)$limit;
    $executeParams[':offset'] = (int)$offset;

    // --- DEBUG LOGGING AFTER PARAMETER FILTERING ---
    error_log("SIGNATORY_LIST_DEBUG: ========== AFTER PARAMETER FILTERING ==========");
    error_log("SIGNATORY_LIST_DEBUG: Total params after filtering: " . count($executeParams));
    error_log("SIGNATORY_LIST_DEBUG: Params keys after filtering: " . implode(', ', array_keys($executeParams)));

    // Check which designationId parameters were removed
    $removedDesignationParams = array_diff($designationIdParams, array_filter(array_keys($executeParams), function($key) {
        return strpos($key, ':designationId_') !== false;
    }));
    error_log("SIGNATORY_LIST_DEBUG: Removed designationId params: " . (empty($removedDesignationParams) ? 'none' : implode(', ', $removedDesignationParams)));

    // Extract all placeholders from SQL
    preg_match_all('/:(\w+)/', $sqlString, $sqlPlaceholderMatches);
    $sqlPlaceholders = array_unique($sqlPlaceholderMatches[1]);
    error_log("SIGNATORY_LIST_DEBUG: Placeholders in SQL: " . implode(', ', $sqlPlaceholders));
    error_log("SIGNATORY_LIST_DEBUG: Total placeholders in SQL: " . count($sqlPlaceholders));

    // Extract parameter keys (without colon)
    $executeParamKeys = array_map(function($key) {
        return ltrim($key, ':');
    }, array_keys($executeParams));
    error_log("SIGNATORY_LIST_DEBUG: Execute param keys (without colon): " . implode(', ', $executeParamKeys));

    // Find mismatches
    $missingInParams = array_diff($sqlPlaceholders, $executeParamKeys);
    $extraInParams = array_diff($executeParamKeys, $sqlPlaceholders);
    error_log("SIGNATORY_LIST_DEBUG: Placeholders in SQL but NOT in params: " . (empty($missingInParams) ? 'none' : implode(', ', $missingInParams)));
    error_log("SIGNATORY_LIST_DEBUG: Params NOT in SQL: " . (empty($extraInParams) ? 'none' : implode(', ', $extraInParams)));

    if (!empty($missingInParams)) {
        error_log("SIGNATORY_LIST_DEBUG: ⚠️ WARNING - Missing parameters that SQL expects!");
    }
    if (!empty($extraInParams)) {
        error_log("SIGNATORY_LIST_DEBUG: ⚠️ WARNING - Extra parameters that SQL doesn't use!");
    }
    error_log("SIGNATORY_LIST_DEBUG: ========== END PARAMETER FILTERING DEBUG ==========");
    // --- END DEBUG LOGGING AFTER FILTERING ---
    
    try {
        $stmt->execute($executeParams);
    } catch (PDOException $e) {
        // Enhanced error logging to diagnose the issue
        error_log("SIGNATORY_LIST_ERROR: ========== SQL EXECUTION ERROR ==========");
        error_log("SIGNATORY_LIST_ERROR: SQL Error: " . $e->getMessage());
        error_log("SIGNATORY_LIST_ERROR: Error Code: " . $e->getCode());
        error_log("SIGNATORY_LIST_ERROR: Is School Admin: " . ($isSchoolAdmin ? 'true' : 'false'));
        error_log("SIGNATORY_LIST_ERROR: SQL Query (first 2000 chars): " . substr($sqlString, 0, 2000));
        error_log("SIGNATORY_LIST_ERROR: Parameters count: " . count($executeParams));
        error_log("SIGNATORY_LIST_ERROR: Parameter keys: " . implode(', ', array_keys($executeParams)));
        error_log("SIGNATORY_LIST_ERROR: Parameter values (first 500 chars): " . substr(json_encode($executeParams, JSON_PRETTY_PRINT), 0, 500));
        
        // Count unique placeholders in SQL
        preg_match_all('/:(\w+)/', $sqlString, $matches);
        $uniquePlaceholders = array_unique($matches[1]);
        error_log("SIGNATORY_LIST_ERROR: Unique placeholders in SQL: " . count($uniquePlaceholders));
        error_log("SIGNATORY_LIST_ERROR: Placeholders: " . implode(', ', $uniquePlaceholders));
        
        // Compare placeholders vs params
        $executeParamKeys = array_map(function($key) {
            return ltrim($key, ':');
        }, array_keys($executeParams));
        $missingInParams = array_diff($uniquePlaceholders, $executeParamKeys);
        $extraInParams = array_diff($executeParamKeys, $uniquePlaceholders);
        error_log("SIGNATORY_LIST_ERROR: Placeholders in SQL but missing in params: " . (empty($missingInParams) ? 'none' : implode(', ', $missingInParams)));
        error_log("SIGNATORY_LIST_ERROR: Params not in SQL: " . (empty($extraInParams) ? 'none' : implode(', ', $extraInParams)));
        
        // Check for designationId params specifically
        $designationIdParamsInExecute = array_filter(array_keys($executeParams), function($key) {
            return strpos($key, ':designationId_') !== false;
        });
        error_log("SIGNATORY_LIST_ERROR: DesignationId params in executeParams: " . (empty($designationIdParamsInExecute) ? 'none' : implode(', ', $designationIdParamsInExecute)));
        error_log("SIGNATORY_LIST_ERROR: ========== END SQL EXECUTION ERROR ==========");
        
        throw new Exception("Database query failed: " . $e->getMessage());
    }
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
    // Ensure type is normalized for response key
    $normalizedType = strtolower($type);
    $responseKey = ($normalizedType === 'faculty') ? 'faculty' : 'students';
    
    // Check if user can perform signatory actions
    // School Administrators and Regular Staff can only perform actions if they're actually assigned as a signatory for this sector/period
    $canPerformActions = true; // Default: can perform actions
    if ($isSchoolAdmin) {
        // Check if School Administrator has any signatory assignments for this sector/period
        $canPerformActions = false; // Default to false for School Admins
        
        error_log("SIGNATORY_LIST_DEBUG: ========== PERMISSION CHECK ==========");
        error_log("SIGNATORY_LIST_DEBUG: Checking can_perform_actions for School Admin");
        error_log("SIGNATORY_LIST_DEBUG: Active Period ID: " . ($activePeriodId ?: 'null'));
        error_log("SIGNATORY_LIST_DEBUG: Query Direct By Term: " . ($queryDirectByTerm ? 'true' : 'false'));
        error_log("SIGNATORY_LIST_DEBUG: Selected Academic Year ID: " . ($selectedAcademicYearId ?: 'null'));
        error_log("SIGNATORY_LIST_DEBUG: Selected Semester ID: " . ($selectedSemesterId ?: 'null'));
        
        // If no designations, they can't perform actions
        if (empty($designationIds)) {
            $canPerformActions = false;
            error_log("SIGNATORY_LIST_DEBUG: No designations - setting canPerformActions to false");
        } else {
            // Determine the sector for checking signatory assignments
            $checkSector = ($type === 'faculty') ? 'Faculty' : $requestSector;
            
            // If there's no period at all, they can't perform actions (view-only mode)
            if (!$activePeriodId && !$queryDirectByTerm) {
                $canPerformActions = false;
                error_log("SIGNATORY_LIST_DEBUG: No active period or term - setting canPerformActions to false (view-only mode)");
            } else if ($activePeriodId) {
                // Check if School Administrator's designation is assigned as signatory for this period
                // Build FRESH placeholders with UNIQUE names to avoid parameter conflicts
                $checkDesignationPlaceholders = [];
                $checkDesignationParams = [];
                foreach ($designationIds as $i => $id) {
                    $key = ":permCheckDesig_$i";  // Unique prefix to avoid conflicts with main query
                    $checkDesignationPlaceholders[] = $key;
                    $checkDesignationParams[$key] = $id;
                }
                $checkDesignationInClause = implode(',', $checkDesignationPlaceholders);
                
                $signatoryCheckStmt = $pdo->prepare("
                    SELECT COUNT(*) 
                    FROM clearance_signatories cs
                    JOIN clearance_forms cf ON cs.clearance_form_id = cf.clearance_form_id
                    JOIN clearance_periods cp ON cf.academic_year_id = cp.academic_year_id 
                        AND cf.semester_id = cp.semester_id
                    WHERE cp.period_id = :periodId 
                        AND cp.sector = :sector
                        AND cs.designation_id IN ($checkDesignationInClause)
                    LIMIT 1
                ");
                $signatoryCheckParams = array_merge(
                    [':periodId' => $activePeriodId, ':sector' => $checkSector], 
                    $checkDesignationParams
                );
                error_log("SIGNATORY_LIST_DEBUG: Executing permission check query with activePeriodId");
                error_log("SIGNATORY_LIST_DEBUG: Permission check SQL: " . substr($signatoryCheckStmt->queryString, 0, 500));
                error_log("SIGNATORY_LIST_DEBUG: Permission check params: " . json_encode($signatoryCheckParams));
                
                try {
                    $signatoryCheckStmt->execute($signatoryCheckParams);
                    $hasSignatoryAssignment = $signatoryCheckStmt->fetchColumn() > 0;
                    $canPerformActions = $hasSignatoryAssignment;
                    error_log("SIGNATORY_LIST_DEBUG: Permission check result - hasSignatoryAssignment: " . ($hasSignatoryAssignment ? 'true' : 'false'));
                } catch (PDOException $e) {
                    error_log("SIGNATORY_LIST_DEBUG: ERROR in permission check query: " . $e->getMessage());
                    error_log("SIGNATORY_LIST_DEBUG: Permission check SQL: " . $signatoryCheckStmt->queryString);
                    error_log("SIGNATORY_LIST_DEBUG: Permission check params: " . json_encode($signatoryCheckParams));
                    $canPerformActions = false; // Default to false on error
                }
            } else if ($queryDirectByTerm && $selectedAcademicYearId && $selectedSemesterId) {
                // Check if School Administrator's designation is assigned as signatory for this term
                // Build FRESH placeholders with UNIQUE names to avoid parameter conflicts
                $checkDesignationPlaceholders = [];
                $checkDesignationParams = [];
                foreach ($designationIds as $i => $id) {
                    $key = ":permCheckDesig_$i";  // Unique prefix to avoid conflicts with main query
                    $checkDesignationPlaceholders[] = $key;
                    $checkDesignationParams[$key] = $id;
                }
                $checkDesignationInClause = implode(',', $checkDesignationPlaceholders);
                
                $signatoryCheckStmt = $pdo->prepare("
                    SELECT COUNT(*) 
                    FROM clearance_signatories cs
                    JOIN clearance_forms cf ON cs.clearance_form_id = cf.clearance_form_id
                    WHERE cf.academic_year_id = :academicYearId 
                        AND cf.semester_id = :semesterId
                        AND cs.designation_id IN ($checkDesignationInClause)
                    LIMIT 1
                ");
                $signatoryCheckParams = array_merge(
                    [':academicYearId' => $selectedAcademicYearId, ':semesterId' => $selectedSemesterId], 
                    $checkDesignationParams
                );
                error_log("SIGNATORY_LIST_DEBUG: Executing permission check query with direct term");
                error_log("SIGNATORY_LIST_DEBUG: Permission check SQL: " . substr($signatoryCheckStmt->queryString, 0, 500));
                error_log("SIGNATORY_LIST_DEBUG: Permission check params: " . json_encode($signatoryCheckParams));
                
                try {
                    $signatoryCheckStmt->execute($signatoryCheckParams);
                    $hasSignatoryAssignment = $signatoryCheckStmt->fetchColumn() > 0;
                    $canPerformActions = $hasSignatoryAssignment;
                    error_log("SIGNATORY_LIST_DEBUG: Permission check result - hasSignatoryAssignment: " . ($hasSignatoryAssignment ? 'true' : 'false'));
                } catch (PDOException $e) {
                    error_log("SIGNATORY_LIST_DEBUG: ERROR in permission check query: " . $e->getMessage());
                    error_log("SIGNATORY_LIST_DEBUG: Permission check SQL: " . $signatoryCheckStmt->queryString);
                    error_log("SIGNATORY_LIST_DEBUG: Permission check params: " . json_encode($signatoryCheckParams));
                    $canPerformActions = false; // Default to false on error
                }
            }
        }
    } else if ($isProgramHead) {
        // Program Head permission check
        // Uses the same logic as api/program-head/is_assigned.php for consistency
        // Checks: (1) department scope, (2) include_program_head setting, (3) active period
        error_log("SIGNATORY_LIST_DEBUG: ========== PROGRAM HEAD PERMISSION CHECK ==========");
        error_log("SIGNATORY_LIST_DEBUG: Checking can_perform_actions for Program Head");
        error_log("SIGNATORY_LIST_DEBUG: Active Period ID: " . ($activePeriodId ?: 'null'));
        error_log("SIGNATORY_LIST_DEBUG: Query Direct By Term: " . ($queryDirectByTerm ? 'true' : 'false'));
        error_log("SIGNATORY_LIST_DEBUG: Program Head Departments: " . json_encode($programHeadDepartments));
        
        $canPerformActions = false; // Default to false for Program Heads
        
        // Check 1: Department scope
        $hasDepartmentScope = !empty($programHeadDepartments);
        error_log("SIGNATORY_LIST_DEBUG: Has Department Scope: " . ($hasDepartmentScope ? 'true' : 'false'));
        
        if (!$hasDepartmentScope) {
            $canPerformActions = false;
            error_log("SIGNATORY_LIST_DEBUG: No department assignments - setting canPerformActions to false");
        } else {
            // Check 2: include_program_head setting from sector_clearance_settings
            $checkSector = ($type === 'faculty') ? 'Faculty' : $requestSector;
            $includePhSetting = 0;
            
            try {
                $settingStmt = $pdo->prepare("SELECT include_program_head FROM sector_clearance_settings WHERE clearance_type = ? LIMIT 1");
                $settingStmt->execute([$checkSector]);
                $settingRow = $settingStmt->fetch(PDO::FETCH_ASSOC);
                if ($settingRow) {
                    $includePhSetting = (int)$settingRow['include_program_head'];
                }
                error_log("SIGNATORY_LIST_DEBUG: include_program_head setting for '$checkSector': " . $includePhSetting);
            } catch (PDOException $e) {
                error_log("SIGNATORY_LIST_DEBUG: ERROR checking include_program_head setting: " . $e->getMessage());
            }
            
            // Check 3: Active period
            $hasActivePeriod = $activePeriodId || $queryDirectByTerm;
            error_log("SIGNATORY_LIST_DEBUG: Has Active Period: " . ($hasActivePeriod ? 'true' : 'false'));
            
            // Final permission: all three checks must pass
            $canPerformActions = $hasDepartmentScope && ($includePhSetting === 1) && $hasActivePeriod;
            
            error_log("SIGNATORY_LIST_DEBUG: Program Head Permission Result - " .
                      "hasDeptScope: " . ($hasDepartmentScope ? 'true' : 'false') . 
                      ", includePhSetting: " . $includePhSetting . 
                      ", hasActivePeriod: " . ($hasActivePeriod ? 'true' : 'false') .
                      ", canPerformActions: " . ($canPerformActions ? 'true' : 'false'));
        }
    } else {
        // Regular Staff permission check
        // They can perform actions if their designation(s) are assigned as signatory for this sector/period
        error_log("SIGNATORY_LIST_DEBUG: ========== REGULAR STAFF PERMISSION CHECK ==========");
        error_log("SIGNATORY_LIST_DEBUG: Checking can_perform_actions for Regular Staff");
        error_log("SIGNATORY_LIST_DEBUG: Active Period ID: " . ($activePeriodId ?: 'null'));
        error_log("SIGNATORY_LIST_DEBUG: Query Direct By Term: " . ($queryDirectByTerm ? 'true' : 'false'));
        error_log("SIGNATORY_LIST_DEBUG: Selected Academic Year ID: " . ($selectedAcademicYearId ?: 'null'));
        error_log("SIGNATORY_LIST_DEBUG: Selected Semester ID: " . ($selectedSemesterId ?: 'null'));
        error_log("SIGNATORY_LIST_DEBUG: Designation Filter: " . ($designationFilter ?: 'none'));
        
        // If no designations, they can't perform actions
        if (empty($designationIds)) {
            $canPerformActions = false;
            error_log("SIGNATORY_LIST_DEBUG: No designations - setting canPerformActions to false");
        } else {
            // Determine the sector for checking signatory assignments
            $checkSector = ($type === 'faculty') ? 'Faculty' : $requestSector;
            
            // If there's no period at all, they can't perform actions (view-only mode)
            if (!$activePeriodId && !$queryDirectByTerm) {
                $canPerformActions = false;
                error_log("SIGNATORY_LIST_DEBUG: No active period or term - setting canPerformActions to false (view-only mode)");
            } else {
                // Determine which designations to check
                // If designation_filter is provided, check only that specific designation
                // Otherwise, check all their designations
                $checkDesignationIds = [];
                
                if (!empty($designationFilter)) {
                    // Find the designation ID for the filtered designation
                    foreach ($staffDesignations as $desig) {
                        if (strcasecmp($desig['designation_name'], $designationFilter) === 0) {
                            $checkDesignationIds[] = $desig['designation_id'];
                            error_log("SIGNATORY_LIST_DEBUG: Checking specific designation: " . $designationFilter . " (ID: " . $desig['designation_id'] . ")");
                            break;
                        }
                    }
                    // If designation filter doesn't match any of their designations, they can't perform actions
                    if (empty($checkDesignationIds)) {
                        $canPerformActions = false;
                        error_log("SIGNATORY_LIST_DEBUG: Designation filter doesn't match any of their designations - setting canPerformActions to false");
                    }
                } else {
                    // Check all their designations
                    $checkDesignationIds = $designationIds;
                    error_log("SIGNATORY_LIST_DEBUG: Checking all designations: " . json_encode($checkDesignationIds));
                }
                
                // Perform permission check if we have designations to check
                if (!empty($checkDesignationIds) && $canPerformActions !== false) {
                    // Build FRESH placeholders with UNIQUE names to avoid parameter conflicts
                    $checkDesignationPlaceholders = [];
                    $checkDesignationParams = [];
                    foreach ($checkDesignationIds as $i => $id) {
                        $key = ":permCheckDesig_$i";  // Unique prefix to avoid conflicts with main query
                        $checkDesignationPlaceholders[] = $key;
                        $checkDesignationParams[$key] = $id;
                    }
                    $checkDesignationInClause = implode(',', $checkDesignationPlaceholders);
                    
                    if ($activePeriodId) {
                        // Check if Regular Staff's designation is assigned as signatory for this period
                        $signatoryCheckStmt = $pdo->prepare("
                            SELECT COUNT(*) 
                            FROM clearance_signatories cs
                            JOIN clearance_forms cf ON cs.clearance_form_id = cf.clearance_form_id
                            JOIN clearance_periods cp ON cf.academic_year_id = cp.academic_year_id 
                                AND cf.semester_id = cp.semester_id
                            WHERE cp.period_id = :periodId 
                                AND cp.sector = :sector
                                AND cs.designation_id IN ($checkDesignationInClause)
                            LIMIT 1
                        ");
                        $signatoryCheckParams = array_merge(
                            [':periodId' => $activePeriodId, ':sector' => $checkSector], 
                            $checkDesignationParams
                        );
                        error_log("SIGNATORY_LIST_DEBUG: Executing permission check query with activePeriodId");
                        error_log("SIGNATORY_LIST_DEBUG: Permission check SQL: " . substr($signatoryCheckStmt->queryString, 0, 500));
                        error_log("SIGNATORY_LIST_DEBUG: Permission check params: " . json_encode($signatoryCheckParams));
                        
                        try {
                            $signatoryCheckStmt->execute($signatoryCheckParams);
                            $hasSignatoryAssignment = $signatoryCheckStmt->fetchColumn() > 0;
                            $canPerformActions = $hasSignatoryAssignment;
                            error_log("SIGNATORY_LIST_DEBUG: Permission check result - hasSignatoryAssignment: " . ($hasSignatoryAssignment ? 'true' : 'false'));
                        } catch (PDOException $e) {
                            error_log("SIGNATORY_LIST_DEBUG: ERROR in permission check query: " . $e->getMessage());
                            error_log("SIGNATORY_LIST_DEBUG: Permission check SQL: " . $signatoryCheckStmt->queryString);
                            error_log("SIGNATORY_LIST_DEBUG: Permission check params: " . json_encode($signatoryCheckParams));
                            $canPerformActions = false; // Default to false on error
                        }
                    } else if ($queryDirectByTerm && $selectedAcademicYearId && $selectedSemesterId) {
                        // Check if Regular Staff's designation is assigned as signatory for this term
                        $signatoryCheckStmt = $pdo->prepare("
                            SELECT COUNT(*) 
                            FROM clearance_signatories cs
                            JOIN clearance_forms cf ON cs.clearance_form_id = cf.clearance_form_id
                            WHERE cf.academic_year_id = :academicYearId 
                                AND cf.semester_id = :semesterId
                                AND cs.designation_id IN ($checkDesignationInClause)
                            LIMIT 1
                        ");
                        $signatoryCheckParams = array_merge(
                            [':academicYearId' => $selectedAcademicYearId, ':semesterId' => $selectedSemesterId], 
                            $checkDesignationParams
                        );
                        error_log("SIGNATORY_LIST_DEBUG: Executing permission check query with direct term");
                        error_log("SIGNATORY_LIST_DEBUG: Permission check SQL: " . substr($signatoryCheckStmt->queryString, 0, 500));
                        error_log("SIGNATORY_LIST_DEBUG: Permission check params: " . json_encode($signatoryCheckParams));
                        
                        try {
                            $signatoryCheckStmt->execute($signatoryCheckParams);
                            $hasSignatoryAssignment = $signatoryCheckStmt->fetchColumn() > 0;
                            $canPerformActions = $hasSignatoryAssignment;
                            error_log("SIGNATORY_LIST_DEBUG: Permission check result - hasSignatoryAssignment: " . ($hasSignatoryAssignment ? 'true' : 'false'));
                        } catch (PDOException $e) {
                            error_log("SIGNATORY_LIST_DEBUG: ERROR in permission check query: " . $e->getMessage());
                            error_log("SIGNATORY_LIST_DEBUG: Permission check SQL: " . $signatoryCheckStmt->queryString);
                            error_log("SIGNATORY_LIST_DEBUG: Permission check params: " . json_encode($signatoryCheckParams));
                            $canPerformActions = false; // Default to false on error
                        }
                    }
                }
            }
        }
    }
    
    // Debug logging
    error_log("SIGNATORY_LIST_DEBUG: ========== FINAL STATE ==========");
    error_log("SIGNATORY_LIST_DEBUG: Response key = " . $responseKey . " (type = " . $type . ", normalized = " . $normalizedType . ")");
    error_log("SIGNATORY_LIST_DEBUG: isSchoolAdmin = " . ($isSchoolAdmin ? 'true' : 'false') . ", canPerformActions = " . ($canPerformActions ? 'true' : 'false'));
    error_log("SIGNATORY_LIST_DEBUG: ========== END REQUEST ==========");
    
    $response = [
        'success' => true,
        'total' => (int)$total,
        'page' => $page,
        'limit' => $limit,
        'stats' => $stats,
        'can_perform_actions' => $canPerformActions, // Flag for frontend to enable/disable action buttons
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
    error_log("SIGNATORY_LIST_ERROR: ========== UNCAUGHT EXCEPTION ==========");
    error_log("SIGNATORY_LIST_ERROR: Exception Type: " . get_class($e));
    error_log("SIGNATORY_LIST_ERROR: Exception Message: " . $e->getMessage());
    error_log("SIGNATORY_LIST_ERROR: Exception File: " . $e->getFile());
    error_log("SIGNATORY_LIST_ERROR: Exception Line: " . $e->getLine());
    error_log("SIGNATORY_LIST_ERROR: Stack Trace: " . $e->getTraceAsString());
    error_log("SIGNATORY_LIST_ERROR: ========== END EXCEPTION ==========");
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server Error: ' . $e->getMessage()
    ]);
}
?>