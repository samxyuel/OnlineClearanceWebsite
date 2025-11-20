<?php
/**
 * API: List Users (Students/Faculty)
 *
 * A flexible endpoint for fetching lists of users with filtering, pagination, and statistics.
 * Designed for administrative views.
 */

require_once __DIR__ . '/../../includes/config/database.php';

// Set dynamic CORS headers (works for both localhost and production)
setCorsHeaders(true, ['GET', 'OPTIONS'], ['Content-Type']);

header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/classes/Auth.php';

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
    if (!$auth->isLoggedIn() || !$auth->hasPermission('view_users')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied.']);
        exit;
    }

    $pdo = Database::getInstance()->getConnection();

    // Parameters
    $type = $_GET['type'] ?? 'student'; // 'student' or 'faculty'
    $sector = $_GET['sector'] ?? null; // 'College', 'Senior High School'
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 20;
    $offset = ($page - 1) * $limit;
    $search = trim($_GET['search'] ?? '');
    $clearanceStatus = $_GET['clearance_status'] ?? '';
    $programId = $_GET['program_id'] ?? '';
    $yearLevel = $_GET['year_level'] ?? '';
    $accountStatus = $_GET['account_status'] ?? '';
    $departmentId = $_GET['departments'] ?? '';
    $schoolTerm = $_GET['school_term'] ?? ''; // e.g., "2024-2025|2"

    // Temporary debugging
    error_log("=== STUDENT LIST API DEBUG ===");
    error_log("Received school_term parameter: " . var_export($schoolTerm, true));

    $baseQuery = "";
    $countQuery = "";
    $params = [];
    $selectFields = "";
    $searchFields = [];
    
    // Process school_term parameter to determine which clearance period to use
    $targetAcademicYearId = null;
    $targetSemesterId = null;
    $targetPeriodEndDate = null;
    $clearanceFormJoin = "";
    
    if (!empty($schoolTerm)) {
        // Parse school_term format: "YEAR|semester_id" (e.g., "2024-2025|2")
        $termParts = explode('|', $schoolTerm);
        $yearName = $termParts[0] ?? '';
        $semesterId = isset($termParts[1]) ? (int)trim($termParts[1]) : null;
        
        // Temporary debugging
        error_log("Parsed school_term - yearName: " . var_export($yearName, true) . ", semesterId: " . var_export($semesterId, true));
        
        if ($yearName && $semesterId) {
            // Resolve academic_year_id from year string
            $ayStmt = $pdo->prepare("SELECT academic_year_id FROM academic_years WHERE year = ? LIMIT 1");
            $ayStmt->execute([$yearName]);
            $targetAcademicYearId = $ayStmt->fetchColumn();
            
            if ($targetAcademicYearId) {
                $targetSemesterId = $semesterId;
                
                // Temporary debugging
                error_log("Found targetAcademicYearId: " . $targetAcademicYearId . ", targetSemesterId: " . $targetSemesterId);
                
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
                    // Temporary debugging
                    error_log("Semester info found: " . json_encode($semesterInfo));
                    
                    // Priority 1: Use semester.ended_at (most accurate - when term was actually ended)
                    if ($semesterInfo['ended_at']) {
                        $targetPeriodEndDate = $semesterInfo['ended_at'];
                        error_log("Using semester.ended_at: " . $targetPeriodEndDate);
                    } 
                    // Priority 2: Use academic_years.ended_at (when academic year was ended)
                    elseif ($semesterInfo['academic_year_ended_at']) {
                        $targetPeriodEndDate = $semesterInfo['academic_year_ended_at'];
                        error_log("Using academic_years.ended_at: " . $targetPeriodEndDate);
                    }
                    // Priority 3: Try clearance_periods.ended_at
                    else {
                        $periodEndedStmt = $pdo->prepare("
                            SELECT MAX(ended_at) 
                            FROM clearance_periods 
                            WHERE academic_year_id = ? AND semester_id = ? AND ended_at IS NOT NULL
                        ");
                        $periodEndedStmt->execute([$targetAcademicYearId, $targetSemesterId]);
                        $periodEndedAt = $periodEndedStmt->fetchColumn();
                        
                        if ($periodEndedAt) {
                            $targetPeriodEndDate = $periodEndedAt;
                            error_log("Using clearance_periods.ended_at: " . $targetPeriodEndDate);
                        }
                        // Priority 4: Try clearance_periods.end_date (only if it's in the past)
                        else {
                            $periodDateStmt = $pdo->prepare("
                                SELECT MAX(end_date) 
                                FROM clearance_periods 
                                WHERE academic_year_id = ? AND semester_id = ? AND status IN ('Ongoing', 'Closed')
                            ");
                            $periodDateStmt->execute([$targetAcademicYearId, $targetSemesterId]);
                            $periodEndDate = $periodDateStmt->fetchColumn();
                            
                            // Only use clearance_periods.end_date if it's actually in the past
                            // If it's today or in the future, it's not a valid historical end date
                            if ($periodEndDate) {
                                $periodEndDateNormalized = date('Y-m-d', strtotime($periodEndDate));
                                $today = date('Y-m-d');
                                
                                // Only use if the period end date is in the past
                                if ($periodEndDateNormalized < $today) {
                                    $targetPeriodEndDate = $periodEndDateNormalized;
                                    error_log("Using clearance_periods.end_date (past date): " . $targetPeriodEndDate);
                                } else {
                                    error_log("Ignoring clearance_periods.end_date (future/current): " . $periodEndDateNormalized);
                                }
                            }
                        }
                    }
                    
                    // Priority 5: Calculate from academic year string (works even when created on same day)
                    // This is the key fix for your test case
                    if (!$targetPeriodEndDate) {
                        $targetPeriodEndDate = calculateTermEndDateFromAcademicYear($yearName, $semesterInfo['semester_name'], $semesterInfo['is_active']);
                        error_log("Calculated term end date from academic year: " . var_export($targetPeriodEndDate, true));
                    }
                } else {
                    error_log("WARNING: Semester info not found for semester_id: " . $targetSemesterId . ", academic_year_id: " . $targetAcademicYearId);
                }
            } else {
                error_log("WARNING: targetAcademicYearId is NULL - academic year not found for: " . var_export($yearName, true));
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
        if ($type === 'student') {
            $clearanceFormJoin = "LEFT JOIN clearance_forms cf ON u.user_id = cf.user_id AND cf.academic_year_id = (SELECT academic_year_id FROM clearance_periods WHERE status = 'Ongoing' AND sector = s.sector LIMIT 1) AND cf.semester_id = (SELECT semester_id FROM clearance_periods WHERE status = 'Ongoing' AND sector = s.sector LIMIT 1)";
        } else {
            $clearanceFormJoin = "LEFT JOIN clearance_forms cf ON u.user_id = cf.user_id AND cf.academic_year_id = (SELECT academic_year_id FROM clearance_periods WHERE status = 'Ongoing' AND sector = 'Faculty' LIMIT 1) AND cf.semester_id = (SELECT semester_id FROM clearance_periods WHERE status = 'Ongoing' AND sector = 'Faculty' LIMIT 1)";
        }
    }

    if ($type === 'student') {
        $baseQuery = "
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            LEFT JOIN programs p ON s.program_id = p.program_id
            LEFT JOIN departments d ON s.department_id = d.department_id
            $clearanceFormJoin
        ";
        $selectFields = "
            s.student_id as id, u.user_id, CONCAT(u.first_name, ' ', u.last_name) as name,
            p.program_name as program, s.year_level, s.section, u.account_status,
            u.created_at as user_created_at,
            COALESCE(cf.clearance_form_progress, 'Unapplied') as clearance_status
        ";
        $searchFields = ['u.first_name', 'u.last_name', 's.student_id', 'p.program_name'];
    } else { // faculty
        $baseQuery = "
            FROM faculty f
            JOIN users u ON f.user_id = u.user_id
            LEFT JOIN departments d ON f.department_id = d.department_id
            $clearanceFormJoin
        ";
        $selectFields = "
            f.employee_number as id, u.user_id, CONCAT(u.first_name, ' ', u.last_name) as name,
            d.department_name as program, f.employment_status as year_level, '' as section, u.account_status,
            u.created_at as user_created_at,
            COALESCE(cf.clearance_form_progress, 'Unapplied') as clearance_status
        ";
        $searchFields = ['u.first_name', 'u.last_name', 'f.employee_number', 'd.department_name'];
    }

    $where = "WHERE 1=1";
    
    // Note: We don't filter out users who didn't exist - we'll mark them as N/A in the response
    // This allows admins to see all users but understand which ones existed during the selected term
    
    if ($sector) {
        $where .= " AND s.sector = :sector"; // This assumes faculty also have a sector column if you filter them by it.
        $params[':sector'] = $sector;
    }
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
    if ($programId) {
        $where .= " AND s.program_id = :programId";
        $params[':programId'] = $programId;
    }
    if ($yearLevel) {
        $where .= " AND s.year_level = :yearLevel";
        $params[':yearLevel'] = $yearLevel;
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
        $where .= ($type === 'student' ? " AND p.department_id = :departmentId" : " AND f.department_id = :departmentId");
        $params[':departmentId'] = $departmentId;
    }

    // Get total count
    $countStmt = $pdo->prepare("SELECT COUNT(u.user_id) $baseQuery $where");
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();

    // Get paginated data
    $dataStmt = $pdo->prepare("SELECT SQL_CALC_FOUND_ROWS $selectFields $baseQuery $where ORDER BY u.last_name, u.first_name LIMIT :limit OFFSET :offset");
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
        
        // Temporary debugging
        error_log("Processing user existence check with termEndDate: " . $termEndDate);
        
        foreach ($results as &$row) {
            $userCreatedAt = $row['user_created_at'] ?? null;
            $userExisted = true;
            
            if ($userCreatedAt) {
                $userCreatedDate = date('Y-m-d', strtotime($userCreatedAt));
                // User existed if they were created on or before the term ended
                $userExisted = ($userCreatedDate <= $termEndDate);
                
                // Temporary debugging for first few users
                if (count($results) <= 5 || !$userExisted) {
                    error_log("User: " . ($row['name'] ?? 'Unknown') . " - Created: " . $userCreatedDate . " vs Term End: " . $termEndDate . " -> Existed: " . ($userExisted ? 'YES' : 'NO'));
                }
            }
            
            $row['user_existed_during_term'] = $userExisted;
            
            // If user didn't exist during term, mark clearance status as N/A
            if (!$userExisted) {
                $row['clearance_status'] = 'N/A';
            }
        }
        unset($row); // Break reference
    } elseif ($targetAcademicYearId && $targetSemesterId) {
        
        // Temporary debugging
        error_log("targetPeriodEndDate is NULL but we have targetAcademicYearId and targetSemesterId - attempting calculation");
        
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
            error_log("Calculated end date (fallback): " . var_export($calculatedEndDate, true));
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
                error_log("WARNING: Could not calculate end date - defaulting all users to existed");
                foreach ($results as &$row) {
                    $row['user_existed_during_term'] = true;
                }
                unset($row);
            }
        } else {
            // Fallback: all users existed if we can't determine
            error_log("WARNING: Semester info not found for fallback calculation");
            foreach ($results as &$row) {
                $row['user_existed_during_term'] = true;
            }
            unset($row);
        }
    } else {
        // For current term, all users existed (or we don't have term info)
        error_log("No target term specified - defaulting all users to existed");
        foreach ($results as &$row) {
            $row['user_existed_during_term'] = true;
        }
        unset($row);
    }
    
    // Temporary debugging - log summary
    $existedCount = count(array_filter($results, fn($r) => ($r['user_existed_during_term'] ?? true) === true));
    $notExistedCount = count($results) - $existedCount;
    error_log("User existence summary - Existed: $existedCount, Not Existed: $notExistedCount, Total: " . count($results));
    error_log("=== END STUDENT LIST API DEBUG ===");

    // Get statistics
    $statsQuery = "SELECT u.account_status, COUNT(*) as count $baseQuery $where GROUP BY u.account_status";
    $statsStmt = $pdo->prepare($statsQuery);
    
    // We need to bind the same parameters as the main query for the stats to be accurate
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
        'graduated' => $statsResults['graduated'] ?? 0,
    ];

    // Temporary debugging - add debug info to response
    $debugInfo = [
        'school_term_received' => $schoolTerm,
        'targetAcademicYearId' => $targetAcademicYearId,
        'targetSemesterId' => $targetSemesterId,
        'targetPeriodEndDate' => $targetPeriodEndDate,
        'existed_count' => count(array_filter($results, fn($r) => ($r['user_existed_during_term'] ?? true) === true)),
        'not_existed_count' => count(array_filter($results, fn($r) => ($r['user_existed_during_term'] ?? false) === false))
    ];
    
    echo json_encode([
        'success' => true,
        'total' => (int)$total,
        'page' => $page,
        'limit' => $limit,
        'stats' => $stats,
        'students' => $results,
        '_debug' => $debugInfo  // Temporary - remove after debugging
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}
?>
