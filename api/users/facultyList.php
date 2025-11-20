<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');

require_once '../../includes/config/database.php';
require_once '../../includes/classes/Auth.php';

/**
 * Calculate term end date from academic year string and semester name
 * This works even when all records are created on the same day
 * 
 * @param string $yearName Academic year string (e.g., "2023-2024")
 * @param string $semesterName Semester name (e.g., "1st", "2nd", "Summer")
 * @param bool $isActive Whether the academic year is currently active
 * @return string|null Calculated end date in Y-m-d format
 */
function calculateTermEndDateFromAcademicYear($yearName, $semesterName, $isActive) {
    // Parse academic year string (e.g., "2023-2024" -> start: 2023, end: 2024)
    $yearParts = explode('-', $yearName);
    if (count($yearParts) !== 2) {
        return null; // Invalid format
    }
    
    $startYear = (int)trim($yearParts[0]);
    $endYear = (int)trim($yearParts[1]);
    
    // Normalize semester name
    $semesterName = strtolower(trim($semesterName));
    
    // Calculate end date based on semester
    if ($semesterName === '1st' || $semesterName === 'first') {
        // 1st Semester typically ends in December of the start year
        // e.g., "2023-2024" 1st Semester -> ends around Dec 31, 2023
        return $startYear . '-12-31';
    } 
    elseif ($semesterName === '2nd' || $semesterName === 'second') {
        // 2nd Semester typically ends in May of the end year
        // e.g., "2023-2024" 2nd Semester -> ends around May 31, 2024
        return $endYear . '-05-31';
    }
    elseif ($semesterName === 'summer') {
        // Summer term typically ends in August of the end year
        // e.g., "2023-2024" Summer -> ends around Aug 31, 2024
        return $endYear . '-08-31';
    }
    
    // Default: if active, use current date; if inactive, use academic year end
    if ($isActive) {
        // For active terms, be more lenient - use current date
        return date('Y-m-d');
    } else {
        // For inactive terms, use academic year end (May 31)
        return $endYear . '-05-31';
    }
}

try {
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied.']);
        exit;
    }

    $pdo = Database::getInstance()->getConnection();

    // Parameters
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 20;
    $offset = ($page - 1) * $limit;
    $search = trim($_GET['search'] ?? '');
    $clearanceStatus = $_GET['clearance_status'] ?? '';
    $accountStatus = $_GET['account_status'] ?? '';
    $employmentStatus = $_GET['employment_status'] ?? '';
    $departmentId = $_GET['departments'] ?? '';
    $schoolTerm = $_GET['school_term'] ?? ''; // e.g., "2024-2025|2"

    // Process school_term parameter to determine which clearance period to use
    $targetAcademicYearId = null;
    $targetSemesterId = null;
    $targetPeriodEndDate = null;
    $clearanceFormJoin = "";
    $params = []; // Initialize params array early for school_term processing
    
    if (!empty($schoolTerm)) {
        // Parse school_term format: "YEAR|semester_id" (e.g., "2024-2025|2")
        $termParts = explode('|', $schoolTerm);
        $yearName = $termParts[0] ?? '';
        $semesterId = isset($termParts[1]) ? (int)trim($termParts[1]) : null;
        
        if ($yearName && $semesterId) {
            // Resolve academic_year_id from year string
            $ayStmt = $pdo->prepare("SELECT academic_year_id FROM academic_years WHERE year = ? LIMIT 1");
            $ayStmt->execute([$yearName]);
            $targetAcademicYearId = $ayStmt->fetchColumn();
            
            if ($targetAcademicYearId) {
                $targetSemesterId = $semesterId;
                
                // Get semester info and academic year info for calculating end dates
                $semesterInfoStmt = $pdo->prepare("
                    SELECT s.ended_at, s.semester_name, ay.ended_at as academic_year_ended_at, ay.is_active
                    FROM semesters s
                    JOIN academic_years ay ON s.academic_year_id = ay.academic_year_id
                    WHERE s.semester_id = ? AND s.academic_year_id = ?
                    LIMIT 1
                ");
                $semesterInfoStmt->execute([$targetSemesterId, $targetAcademicYearId]);
                $semesterInfo = $semesterInfoStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($semesterInfo) {
                    // Priority 1: Use semester.ended_at (most accurate - when term was actually ended)
                    if ($semesterInfo['ended_at']) {
                        $targetPeriodEndDate = $semesterInfo['ended_at'];
                    } 
                    // Priority 2: Use academic_years.ended_at (when academic year was ended)
                    elseif ($semesterInfo['academic_year_ended_at']) {
                        $targetPeriodEndDate = $semesterInfo['academic_year_ended_at'];
                    }
                    // Priority 3: Try clearance_periods.ended_at
                    else {
                        $periodEndedStmt = $pdo->prepare("
                            SELECT MAX(ended_at) 
                            FROM clearance_periods 
                            WHERE academic_year_id = ? AND semester_id = ? AND sector = 'Faculty' AND ended_at IS NOT NULL
                        ");
                        $periodEndedStmt->execute([$targetAcademicYearId, $targetSemesterId]);
                        $periodEndedAt = $periodEndedStmt->fetchColumn();
                        
                        if ($periodEndedAt) {
                            $targetPeriodEndDate = $periodEndedAt;
                        }
                        // Priority 4: Try clearance_periods.end_date
                        else {
                            $periodDateStmt = $pdo->prepare("
                                SELECT MAX(end_date) 
                                FROM clearance_periods 
                                WHERE academic_year_id = ? AND semester_id = ? AND sector = 'Faculty' AND status IN ('Ongoing', 'Closed')
                            ");
                            $periodDateStmt->execute([$targetAcademicYearId, $targetSemesterId]);
                            $targetPeriodEndDate = $periodDateStmt->fetchColumn();
                        }
                    }
                    
                    // Priority 5: Calculate from academic year string (works even when created on same day)
                    // This is the key fix for your test case
                    if (!$targetPeriodEndDate) {
                        $targetPeriodEndDate = calculateTermEndDateFromAcademicYear($yearName, $semesterInfo['semester_name'], $semesterInfo['is_active']);
                    }
                }
            }
        }
    }
    
    // Build clearance_forms join based on whether school_term is specified
    if ($targetAcademicYearId && $targetSemesterId) {
        // Use the specified term
        $clearanceFormJoin = "LEFT JOIN clearance_forms cf ON u.user_id = cf.user_id AND cf.academic_year_id = :targetAcademicYearId AND cf.semester_id = :targetSemesterId";
        $params[':targetAcademicYearId'] = $targetAcademicYearId;
        $params[':targetSemesterId'] = $targetSemesterId;
    } else {
        // Default to Ongoing period (original behavior)
        $clearanceFormJoin = "LEFT JOIN clearance_forms cf ON u.user_id = cf.user_id AND cf.academic_year_id = (SELECT academic_year_id FROM clearance_periods WHERE status = 'Ongoing' AND sector = 'Faculty' LIMIT 1) AND cf.semester_id = (SELECT semester_id FROM clearance_periods WHERE status = 'Ongoing' AND sector = 'Faculty' LIMIT 1)";
    }
    
    $baseQuery = "
        FROM faculty f
        JOIN users u ON f.user_id = u.user_id
        LEFT JOIN departments d ON f.department_id = d.department_id
        $clearanceFormJoin
    ";
    $selectFields = "
        f.employee_number, u.user_id, u.first_name, u.last_name, u.middle_name,
        u.email, u.contact_number, d.department_name, f.employment_status, u.account_status,
        u.created_at as user_created_at,
        COALESCE(cf.clearance_form_progress, 'Unapplied') as clearance_status
    ";
    $searchFields = ['u.first_name', 'u.last_name', 'f.employee_number', 'd.department_name'];

    $where = "WHERE 1=1";
    // $params already initialized above for school_term processing

    if ($search) {
        $searchClauses = [];
        $searchParamIndex = 0;

        // Add a clause for full name search first
        $searchClauses[] = "CONCAT(u.first_name, ' ', u.last_name) LIKE :search_fullname";
        $params[':search_fullname'] = "%$search%";

        foreach ($searchFields as $field) {
            // Avoid duplicating name search which is already handled by CONCAT
            if ($field !== 'u.first_name' && $field !== 'u.last_name') {
                $paramName = ":search" . $searchParamIndex++;
                $searchClauses[] = "$field LIKE $paramName";
                $params[$paramName] = "%$search%";
            }
        }
        $where .= " AND (" . implode(' OR ', $searchClauses) . ")";
    }
    if ($employmentStatus) {
        $where .= " AND f.employment_status = :employmentStatus";
        $params[':employmentStatus'] = $employmentStatus;
    }
    if ($accountStatus) {
        $where .= " AND u.account_status = :accountStatus";
        $params[':accountStatus'] = $accountStatus;
    }
    if ($clearanceStatus) {
        $where .= " AND COALESCE(cf.clearance_form_progress, 'Unapplied') = :clearanceStatus";
        $params[':clearanceStatus'] = $clearanceStatus;
    }
    if ($departmentId) {
        $where .= " AND f.department_id = :departmentId";
        $params[':departmentId'] = $departmentId;
    }

    // Get total count
    $countStmt = $pdo->prepare("SELECT COUNT(u.user_id) $baseQuery $where");
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();

    // Get paginated data
    $dataStmt = $pdo->prepare("SELECT $selectFields $baseQuery $where ORDER BY u.last_name, u.first_name LIMIT :limit OFFSET :offset");
    $dataStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    foreach ($params as $key => &$val) {
        $dataStmt->bindParam($key, $val);
    }
    $dataStmt->execute();
    $results = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process results to add user existence flag and adjust clearance status for non-existent users
    if ($targetPeriodEndDate) {
        // Normalize the end date (handle both date and datetime formats)
        $termEndDate = date('Y-m-d', strtotime($targetPeriodEndDate));
        
        foreach ($results as &$row) {
            $userCreatedAt = $row['user_created_at'] ?? null;
            $userExisted = true;
            
            if ($userCreatedAt) {
                $userCreatedDate = date('Y-m-d', strtotime($userCreatedAt));
                // User existed if they were created on or before the term ended
                $userExisted = ($userCreatedDate <= $termEndDate);
            }
            
            $row['user_existed_during_term'] = $userExisted;
            
            // If user didn't exist during term, mark clearance status as N/A
            if (!$userExisted) {
                $row['clearance_status'] = 'N/A';
            }
        }
        unset($row); // Break reference
    } elseif ($targetAcademicYearId && $targetSemesterId) {
        // Even if we don't have an end date, if we have a target term, try to calculate it
        // This handles edge cases where calculation might have failed
        $semesterInfoStmt = $pdo->prepare("
            SELECT s.semester_name, ay.is_active
            FROM semesters s
            JOIN academic_years ay ON s.academic_year_id = ay.academic_year_id
            WHERE s.semester_id = ? AND s.academic_year_id = ?
            LIMIT 1
        ");
        $semesterInfoStmt->execute([$targetSemesterId, $targetAcademicYearId]);
        $semesterInfo = $semesterInfoStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($semesterInfo) {
            $calculatedEndDate = calculateTermEndDateFromAcademicYear($yearName, $semesterInfo['semester_name'], $semesterInfo['is_active']);
            if ($calculatedEndDate) {
                $termEndDate = $calculatedEndDate;
                
                foreach ($results as &$row) {
                    $userCreatedAt = $row['user_created_at'] ?? null;
                    $userExisted = true;
                    
                    if ($userCreatedAt) {
                        $userCreatedDate = date('Y-m-d', strtotime($userCreatedAt));
                        $userExisted = ($userCreatedDate <= $termEndDate);
                    }
                    
                    $row['user_existed_during_term'] = $userExisted;
                    if (!$userExisted) {
                        $row['clearance_status'] = 'N/A';
                    }
                }
                unset($row);
            } else {
                // Fallback: all users existed if we can't determine
                foreach ($results as &$row) {
                    $row['user_existed_during_term'] = true;
                }
                unset($row);
            }
        } else {
            // Fallback: all users existed if we can't determine
            foreach ($results as &$row) {
                $row['user_existed_during_term'] = true;
            }
            unset($row);
        }
    } else {
        // For current term, all users existed (or we don't have term info)
        foreach ($results as &$row) {
            $row['user_existed_during_term'] = true;
        }
        unset($row);
    }

    // Get statistics
    $statsQuery = "SELECT u.account_status, COUNT(*) as count $baseQuery $where GROUP BY u.account_status";
    $statsStmt = $pdo->prepare($statsQuery);

    // Bind the same parameters as the main query for accurate stats
    $statsParams = $params;
    foreach ($statsParams as $key => &$val) {
        $statsStmt->bindParam($key, $val);
    }
    $statsStmt->execute();
    $statsResults = $statsStmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $stats = [
        'total' => array_sum($statsResults),
        'active' => $statsResults['active'] ?? 0,
        'inactive' => $statsResults['inactive'] ?? 0,
        'resigned' => $statsResults['resigned'] ?? 0,
    ];

    echo json_encode([
        'success' => true,
        'total' => (int)$total,
        'page' => $page,
        'limit' => $limit,
        'stats' => $stats,
        'faculty' => $results
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}
?>
