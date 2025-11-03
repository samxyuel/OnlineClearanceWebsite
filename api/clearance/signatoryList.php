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
    $userRole = $auth->getRoleName();
    $isAdmin = in_array($userRole, ['Admin', 'School Administrator']);
    $pdo = Database::getInstance()->getConnection();

    $designationId = null;
    $designationName = '';
    $isProgramHead = false;
    $programHeadDepartments = [];
    $isShsProgramHead = false;

    if (!$isAdmin) {
        // 1. Get the staff member's designation ID if not an Admin
        $staffStmt = $pdo->prepare("
            SELECT s.designation_id, d.designation_name 
            FROM staff s
            JOIN designations d ON s.designation_id = d.designation_id
            WHERE s.user_id = ? AND s.is_active = 1
        ");
        $staffStmt->execute([$userId]);
        $staffInfo = $staffStmt->fetch(PDO::FETCH_ASSOC);

        if (!$staffInfo) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access Denied: You are not an active staff member with a designation.']);
            exit;
        }
        $designationId = $staffInfo['designation_id'];
        $designationName = $staffInfo['designation_name'];
        $isProgramHead = (strcasecmp($designationName, 'Program Head') === 0);
    }

    if ($isProgramHead) {
        // Get departments assigned to this Program Head
        $phDeptsStmt = $pdo->prepare("SELECT sa.department_id, s.sector_name FROM sector_signatory_assignments sa JOIN departments d ON sa.department_id = d.department_id JOIN sectors s ON d.sector_id = s.sector_id WHERE sa.user_id = ? AND sa.designation_id = ? AND sa.is_active = 1");
        $phDeptsStmt->execute([$userId, $designationId]);
        $programHeadDepartments = $phDeptsStmt->fetchAll(PDO::FETCH_ASSOC);
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
    $departmentId = $_GET['departments'] ?? ''; // Added to handle department filter

    // If a school term is selected, look for periods within that term (Ongoing or Closed)
    // Otherwise, default to the current 'Ongoing' period.
    if (!empty($schoolTerm)) {
        $termParts = explode('|', $schoolTerm);
        $yearName = $termParts[0] ?? '';
        $semesterId = $termParts[1] ?? 0;

        $periodQuery = "SELECT cp.period_id FROM clearance_periods cp JOIN academic_years ay ON cp.academic_year_id = ay.academic_year_id WHERE ay.year = :yearName AND cp.semester_id = :semesterId AND cp.status IN ('Ongoing', 'Closed')";
        $periodParams = [':yearName' => $yearName, ':semesterId' => $semesterId];
    } else {
        $periodQuery = "SELECT period_id FROM clearance_periods WHERE status = 'Ongoing'";
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

    // 4. Build the query based on type
    if (strtolower($type) === 'faculty') {
        $select = "
            SELECT SQL_CALC_FOUND_ROWS
                f.employee_number as id,
                u.user_id,
                u.first_name,
                u.last_name,
                d.department_name as program,
                f.employment_status,
                f.employment_status as year_level,
                '' as section, -- Faculty don't have sections
                u.account_status as account_status,
                cf.clearance_form_progress as clearance_status,
                NULL as signatory_id, -- Not relevant for admin view
                cf.clearance_form_id
        ";
        $from = "
            FROM faculty f
            JOIN users u ON f.user_id = u.user_id
            LEFT JOIN departments d ON f.department_id = d.department_id
            JOIN clearance_periods cp ON cp.sector = 'Faculty' AND cp.status IN ('Ongoing', 'Closed')
            LEFT JOIN clearance_forms cf ON f.user_id = cf.user_id AND cf.academic_year_id = cp.academic_year_id AND cf.semester_id = cp.semester_id
        ";
        $searchFields = ['u.first_name', 'u.last_name', 'f.employee_number', 'd.department_name'];
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
                cf.clearance_form_progress as clearance_status,
                NULL as signatory_id, -- Not relevant for admin view
                cf.clearance_form_id
        ";
        $from = "
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            LEFT JOIN programs p ON s.program_id = p.program_id
            LEFT JOIN departments d ON s.department_id = d.department_id
            LEFT JOIN clearance_periods cp ON cp.sector = s.sector AND cp.status IN ('Ongoing', 'Closed')
            LEFT JOIN clearance_forms cf ON s.user_id = cf.user_id AND cf.academic_year_id = cp.academic_year_id AND cf.semester_id = cp.semester_id
        ";
        $searchFields = ['u.first_name', 'u.last_name', 's.student_id', 'p.program_code'];
    }

    // If not an admin, add the signatory-specific join
    if (!$isAdmin) {
        $from .= " LEFT JOIN clearance_signatories cs ON cf.clearance_form_id = cs.clearance_form_id AND cs.designation_id = :designationId";
        $select = str_replace('cf.clearance_form_progress as clearance_status', 'cs.action as clearance_status', $select);
        $select = str_replace('NULL as signatory_id', 'cs.signatory_id', $select);
    }

    $where = " WHERE 1=1"; // The JOINs already filter by designation, but we can add sector check for robustness if needed.
    $params = [];
    if (!$isAdmin) $params[':designationId'] = $designationId;

    // Apply department scoping for Program Heads
    if (!$isAdmin && $isProgramHead && !empty($programHeadDepartments)) {
        $deptIds = array_column($programHeadDepartments, 'department_id');
        $isShsProgramHead = in_array('Senior High School', array_column($programHeadDepartments, 'sector_name'));

        // Special case for SHS Program Head: if they manage an SHS dept, they see all SHS students.
        if ($type === 'student' && $requestSector === 'Senior High School' && $isShsProgramHead) {
            // The `s.sector = :requestSector` filter below will handle this. No extra department scoping needed.
        } else {
            if (!empty($deptIds)) {
                $deptPlaceholders = [];
                foreach ($deptIds as $i => $id) {
                    $key = ":dept_id_$i";
                    $deptPlaceholders[] = $key;
                    $params[$key] = $id;
                }
                $inClause = implode(',', $deptPlaceholders);

                $deptColumn = ($type === 'faculty') ? 'f.department_id' : 'p.department_id';
                $where .= " AND $deptColumn IN ($inClause)";
            }
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
        $where .= " AND COALESCE(" . ($isAdmin ? "cf.clearance_form_progress" : "cs.action") . ", 'Unapplied') = :clearanceStatus";
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

    if (!empty($schoolTerm)) {
        $termParts = explode('|', $schoolTerm);
        if (count($termParts) === 2) {
            $yearName = $termParts[0];
            $semesterId = $termParts[1];
            
            $where .= " AND cp.academic_year_id = (SELECT academic_year_id FROM academic_years WHERE year = :yearName LIMIT 1)";
            $where .= " AND cp.semester_id = :semesterId";
            $params[':yearName'] = $yearName;
            $params[':semesterId'] = $semesterId;
        }
    }

    if (!empty($programId)) {
        $where .= " AND p.program_id = :programId";
        $params[':programId'] = $programId;
    }

    if (!empty($yearLevel)) {
        $where .= " AND s.year_level = :yearLevel";
        $params[':yearLevel'] = $yearLevel;
    }

    if (!empty($departmentId)) {
        $where .= " AND s.department_id = :departmentId";
        $params[':departmentId'] = $departmentId;
    }

    // Add sector filtering for students
    if ($type === 'student' && !empty($requestSector)) {
        // Assuming 'students' table has a 'sector' column ('College' or 'Senior High School')
        $where .= " AND s.sector = :requestSector";
        $params[':requestSector'] = $requestSector;
    }

    // Pending First and Rejected Second Sorting
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
    $stmt = $pdo->prepare($select . $from . $where . $orderBy . $limitClause);
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
    $statsQuery = "SELECT u.account_status, COUNT(*) as count FROM users u JOIN students s ON u.user_id = s.user_id WHERE s.sector = :requestSector GROUP BY u.account_status";
    $statsStmt = $pdo->prepare($statsQuery);
    $statsStmt->execute([':requestSector' => $requestSector]);
    $statsResults = $statsStmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $stats = [
        'total' => array_sum($statsResults),
        'active' => $statsResults['active'] ?? 0,
        'inactive' => $statsResults['inactive'] ?? 0,
        'graduated' => $statsResults['graduated'] ?? 0, // Assuming 'graduated' is a possible status
    ];


    // 5. Format and return the response
    $responseKey = ($type === 'faculty') ? 'faculty' : 'students';
    $response = [
        'success' => true,
        'total' => (int)$total,
        'page' => $page,
        'limit' => $limit,
        'stats' => $stats,
        $responseKey => array_map(function ($item) {
            return [
                'id' => $item['id'],
                'user_id' => $item['user_id'],
                'name' => trim($item['first_name'] . ' ' . $item['last_name']),
                'program' => $item['program'],
                'year_level' => $item['year_level'], // For faculty, this is employment_status
                'employment_status' => $item['employment_status'] ?? null, // Explicitly add employment_status
                'section' => $item['section'],
                'account_status' => $item['account_status'],
                'clearance_status' => $item['clearance_status'] ?? 'Unapplied',
                'clearance_form_id' => $item['clearance_form_id'],
                'signatory_id' => $item['signatory_id']
            ];
        }, $results)
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