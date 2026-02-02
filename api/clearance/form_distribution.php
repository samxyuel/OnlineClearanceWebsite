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

// Only execute routing code if this file is accessed directly (not included)
// Check if this file is the main script being executed
$isDirectAccess = (
    (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) ||
    (isset($_SERVER['SCRIPT_NAME']) && basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) ||
    (isset($_SERVER['PHP_SELF']) && basename($_SERVER['PHP_SELF']) === basename(__FILE__))
);

if ($isDirectAccess) {
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
        
        // Debug: Check actual connection collation
        $collationCheck = $connection->query("SELECT @@collation_connection, @@collation_database, @@character_set_connection")->fetch(PDO::FETCH_ASSOC);
        error_log("🔍 FORM DISTRIBUTION COLLATION CHECK: " . json_encode($collationCheck));
        
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
    exit; // Exit after handling direct access
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
    
    // Check for an optional single user_id
    $targetUserId = isset($input['user_id']) ? (int)$input['user_id'] : null;
    
    if ($targetUserId) {
        error_log("🚀 FORM DISTRIBUTION: Starting single-user distribution for user_id: $targetUserId, type: $clearanceType, AY: $academicYearId, Semester: $semesterId");
    } else {
        error_log("🚀 FORM DISTRIBUTION: Starting bulk distribution for $clearanceType, AY: $academicYearId, Semester: $semesterId");
    }
    
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
        if ($targetUserId) {
            // If a single user is specified, only fetch that user's data
            $eligibleUsers = getSingleEligibleUser($connection, $targetUserId, $clearanceType);
        } else {
            $eligibleUsers = getEligibleUsers($connection, $clearanceType);
        }
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
        $assistantPrincipal = null; // Initialize assistant principal variable
        
        // Step 2.5: Check if Program Head should be dynamically added
        $settingsStmt = $connection->prepare("SELECT include_program_head FROM sector_clearance_settings WHERE clearance_type = ?");
        $settingsStmt->execute([$clearanceType]);
        $settings = $settingsStmt->fetch(PDO::FETCH_ASSOC);

        if ($settings && $settings['include_program_head'] == 1) {
            error_log("🚀 FORM DISTRIBUTION: 'Include Program Head' is enabled for $clearanceType. Adding to signatories.");
            // Get the designation_id for 'Program Head'
            $phStmt = $connection->prepare("SELECT designation_id FROM designations WHERE designation_name = 'Program Head'");
            $phStmt->execute();
            $programHeadDesignationId = $phStmt->fetchColumn();

            if ($programHeadDesignationId) {
                // Add a placeholder for the dynamic Program Head signatory
                $signatoryAssignments[] = [
                    'designation_id' => $programHeadDesignationId,
                    'designation_name' => 'Program Head',
                    'is_program_head' => true // Custom flag to identify this special signatory
                ];
            }
        }

        if (empty($signatoryAssignments)) {
            $connection->rollback();
            echo json_encode([
                'success' => false, 
                'message' => "No signatory assignments found for $clearanceType. Please assign signatories first."
            ]);
            return;
        }
        
        // De-duplicate signatory assignments to ensure one signatory per designation
        $uniqueSignatoryAssignments = [];
        $seenDesignations = [];
        foreach ($signatoryAssignments as $assignment) {
            if (!in_array($assignment['designation_id'], $seenDesignations)) {
                $uniqueSignatoryAssignments[] = $assignment;
                $seenDesignations[] = $assignment['designation_id'];
            }
        }
        $signatoryAssignments = $uniqueSignatoryAssignments;
        error_log("📝 FORM DISTRIBUTION: Found " . count($signatoryAssignments) . " unique signatory assignments for $clearanceType after de-duplication.");


        // Step 3: Create clearance forms for all eligible users
        $formsCreated = 0;
        $signatoriesAssigned = 0;
        $formsSkipped = 0;
        $totalUsers = count($eligibleUsers);
        $processedCount = 0;
        $startTime = microtime(true);
        
        error_log("📊 FORM DISTRIBUTION: Starting to process $totalUsers users for $clearanceType");
        
        foreach ($eligibleUsers as $user) {
            $processedCount++;
            $userStartTime = microtime(true);
            // Check if form already exists
            $existingForm = checkExistingForm($connection, $user['user_id'], $academicYearId, $semesterId, $clearanceType);
            
            if ($existingForm) {
                // Form exists, get its ID to check for signatories
                $clearanceFormId = $existingForm['clearance_form_id'];
                error_log("📝 FORM DISTRIBUTION: Form {$clearanceFormId} already exists for user {$user['user_id']}. Checking for missing signatories.");

                // Get currently assigned designation IDs for this form
                $stmt = $connection->prepare("SELECT designation_id FROM clearance_signatories WHERE clearance_form_id = ?");
                $stmt->execute([$clearanceFormId]);
                $existingDesignationIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

                // Get required designation IDs from the current sector assignments
                $requiredDesignationIds = array_column($signatoryAssignments, 'designation_id');

                // Find which required designations are missing from the form
                $missingDesignationIds = array_diff($requiredDesignationIds, $existingDesignationIds);

                if (empty($missingDesignationIds)) {
                    error_log("✅ FORM DISTRIBUTION: All required signatories already exist for form {$clearanceFormId}. Skipping.");
                    $formsSkipped++;
                    continue;
                }

                // Filter the main assignments list to only include the missing ones
                $missingAssignments = array_filter($signatoryAssignments, function($assignment) use ($missingDesignationIds) {
                    return in_array($assignment['designation_id'], $missingDesignationIds);
                });

                error_log("⚠️ FORM DISTRIBUTION: Form {$clearanceFormId} is missing " . count($missingAssignments) . " signatories. Assigning now.");
                $signatoryAssignmentsToProcess = $missingAssignments;
            } else {
                // Create clearance form if it doesn't exist and get its generated ID
                $clearanceFormId = createClearanceForm($connection, $user, $academicYearId, $semesterId, $clearanceType);
                $formsCreated++;
                $signatoryAssignmentsToProcess = $signatoryAssignments;
            }
            
            // Assign signatories to the form
            $assignedCount = assignSignatoriesToForm($connection, $clearanceFormId, $signatoryAssignmentsToProcess, $user, $clearanceType);
            $signatoriesAssigned += $assignedCount;

            // Explicitly add Assistant Principal if found and not already on the form
            if ($assistantPrincipal) {
                $stmt = $connection->prepare("SELECT COUNT(*) FROM clearance_signatories WHERE clearance_form_id = ? AND designation_id = ?");
                $stmt->execute([$clearanceFormId, $assistantPrincipal['designation_id']]);
                if ($stmt->fetchColumn() == 0) {
                    error_log("🏫 FORM DISTRIBUTION (SHS): Explicitly assigning Assistant Principal (user_id: {$assistantPrincipal['user_id']}) to form {$clearanceFormId}.");
                    $stmt = $connection->prepare("INSERT INTO clearance_signatories (clearance_form_id, designation_id, actual_user_id, action, created_at) VALUES (?, ?, ?, 'Unapplied', NOW())");
                    $stmt->execute([$clearanceFormId, $assistantPrincipal['designation_id'], $assistantPrincipal['user_id']]);
                    $signatoriesAssigned++;
                } else {
                    error_log("🏫 FORM DISTRIBUTION (SHS): Assistant Principal already assigned to form {$clearanceFormId}. Skipping.");
                }
            }
            
            $userDuration = round((microtime(true) - $userStartTime) * 1000, 2);
            error_log("✅ FORM DISTRIBUTION: Processed form {$clearanceFormId} for {$user['first_name']} {$user['last_name']} with $assignedCount signatories (took {$userDuration}ms)");
            
            // Log progress every 10 users or at milestones
            if ($processedCount % 10 == 0 || $processedCount == $totalUsers) {
                $elapsed = round((microtime(true) - $startTime), 2);
                $avgTimePerUser = round($elapsed / $processedCount, 2);
                $estimatedRemaining = $totalUsers > $processedCount ? round(($totalUsers - $processedCount) * $avgTimePerUser, 2) : 0;
                
                error_log("📊 FORM DISTRIBUTION PROGRESS: $processedCount of $totalUsers users processed | Forms: $formsCreated created, $formsSkipped skipped | Elapsed: {$elapsed}s | Avg: {$avgTimePerUser}s/user | Est. remaining: {$estimatedRemaining}s");
            }
        }
        
        // Commit transaction
        $connection->commit();
        
        $totalDuration = round((microtime(true) - $startTime), 2);
        error_log("🎉 FORM DISTRIBUTION: Successfully distributed $formsCreated forms with $signatoriesAssigned total signatory assignments");
        error_log("⏱️ FORM DISTRIBUTION: Completed $processedCount of $totalUsers users in {$totalDuration} seconds");
        
        echo json_encode([
            'success' => true,
            'message' => "Successfully distributed clearance forms for $clearanceType",
            'forms_created' => $formsCreated,
            'forms_skipped' => $formsSkipped,
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
 * Get a single eligible user for a specific clearance type
 */
function getSingleEligibleUser($connection, $userId, $clearanceType) {
    $sql = "";
    $params = [$userId];

    switch ($clearanceType) {
        case 'College':
        case 'Senior High School':
            $sql = "
                SELECT DISTINCT u.user_id, u.first_name, u.last_name, u.username, 
                       p.program_name as program, s.department_id, d.department_name
                FROM users u
                INNER JOIN students s ON u.user_id = s.user_id
                INNER JOIN departments d ON s.department_id = d.department_id
                LEFT JOIN programs p ON s.program_id = p.program_id
                WHERE u.user_id = ? AND u.account_status = 'active'
            ";
            break;
            
        case 'Faculty':
            $sql = "
                SELECT DISTINCT u.user_id, u.first_name, u.last_name, u.username,
                       f.employment_status, f.department_id, d.department_name
                FROM users u
                INNER JOIN faculty f ON u.user_id = f.user_id
                INNER JOIN departments d ON f.department_id = d.department_id
                WHERE u.user_id = ? AND u.account_status = 'active'
            ";
            break;
        
        default:
            return [];
    }

    $stmt = $connection->prepare($sql);
    $stmt->execute($params);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Return the user as an array containing a single user, to match the format of getEligibleUsers
    return $user ? [$user] : [];
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
                       p.program_name as program, s.department_id, d.department_name
                FROM users u
                INNER JOIN students s ON u.user_id = s.user_id
                INNER JOIN departments d ON s.department_id = d.department_id
                LEFT JOIN programs p ON s.program_id = p.program_id
                INNER JOIN sectors sec ON d.sector_id = sec.sector_id
                WHERE sec.sector_name = 'College' 
                AND u.account_status = 'active'
                ORDER BY u.last_name, u.first_name
            ";
            break;
            
        case 'Senior High School':
            $sql = "
                SELECT DISTINCT u.user_id, u.first_name, u.last_name, u.username, 
                       p.program_name as program, s.department_id, d.department_name
                FROM users u
                INNER JOIN students s ON u.user_id = s.user_id
                INNER JOIN departments d ON s.department_id = d.department_id
                LEFT JOIN programs p ON s.program_id = p.program_id
                INNER JOIN sectors sec ON d.sector_id = sec.sector_id
                WHERE sec.sector_name = 'Senior High School' 
                AND u.account_status = 'active'
                ORDER BY u.last_name, u.first_name
            ";
            break;
            
        case 'Faculty':
            $sql = "
                SELECT DISTINCT u.user_id, u.first_name, u.last_name, u.username,
                       f.employment_status, f.department_id, d.department_name,
                       st.staff_category
                FROM users u
                INNER JOIN faculty f ON u.user_id = f.user_id
                LEFT JOIN departments d ON f.department_id = d.department_id
                LEFT JOIN staff st ON u.user_id = st.user_id
                WHERE u.account_status = 'active'
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
    // Generate a unique clearance form ID
    $year = date('Y');
    // Build pattern in PHP to avoid server collation issues with string literals in CONCAT
    // This ensures the parameter binding respects connection collation
    $pattern = "CF-$year-%";
    $stmt = $connection->prepare("SELECT clearance_form_id FROM clearance_forms WHERE clearance_form_id COLLATE utf8mb4_general_ci LIKE ? COLLATE utf8mb4_general_ci ORDER BY clearance_form_id DESC LIMIT 1");
    $stmt->execute([$pattern]);
    $lastId = $stmt->fetchColumn();
    $nextNum = $lastId ? (int)substr($lastId, -5) + 1 : 1;
    $clearanceFormId = "CF-$year-" . str_pad($nextNum, 5, '0', STR_PAD_LEFT);

    $sql = "
        INSERT INTO clearance_forms (
            clearance_form_id,
            user_id,
            academic_year_id,
            semester_id,
            clearance_type,
            clearance_form_progress,
            created_at
        ) VALUES (?, ?, ?, ?, ?, 'unapplied', NOW())
    ";
    
    $stmt = $connection->prepare($sql);
    $stmt->execute([
        $clearanceFormId,
        $user['user_id'],
        $academicYearId,
        $semesterId,
        $clearanceType
    ]);
    
    return $clearanceFormId;
}

/**
 * Assign signatories to a clearance form
 */
function assignSignatoriesToForm($connection, $clearanceFormId, $signatoryAssignments, $user, $clearanceType) {
    $assignedCount = 0;
    
    // The clearanceFormId is already the correct varchar ID, no need to look it up again.
    if (!$clearanceFormId) {
        error_log("❌ FORM DISTRIBUTION: Cannot assign signatories, clearanceFormId is empty.");
        return 0;
    }
    
    foreach ($signatoryAssignments as $assignment) {
        // Check if signatory already exists for this form to prevent duplicates
        // Use COUNT with explicit type casting for more reliable checking
        $checkStmt = $connection->prepare("
            SELECT COUNT(*) as count FROM clearance_signatories 
            WHERE clearance_form_id = ? AND designation_id = ?
        ");
        $checkStmt->execute([$clearanceFormId, (int)$assignment['designation_id']]);
        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
        $exists = (int)($result['count'] ?? 0);
        
        if ($exists > 0) {
            // Signatory already exists, skip to prevent duplicate
            error_log("⚠️ FORM DISTRIBUTION: Signatory with designation_id {$assignment['designation_id']} ({$assignment['designation_name']}) already exists for form {$clearanceFormId}. Count: {$exists}. Skipping duplicate.");
            continue;
        }
        
        $isProgramHeadSignatory = false;
        if ($clearanceType === 'Senior High School') {
            // For SHS, the "Assistant Principal" is the program head.
            $isProgramHeadSignatory = (strcasecmp($assignment['designation_name'], 'Assistant Principal') === 0);
        } else {
            // For other sectors, the "Program Head" is the program head.
            $isProgramHeadSignatory = (isset($assignment['is_program_head']) && $assignment['is_program_head'] === true);
        }

        $isUserAProgramHead = (isset($user['staff_category']) && strcasecmp($user['staff_category'], 'Program Head') === 0);

        // If the signatory is 'Program Head' and the user is also a 'Program Head', auto-approve.
        if ($clearanceType === 'Faculty' && $isProgramHeadSignatory && $isUserAProgramHead) {
            error_log("✅ AUTO-APPROVING 'Program Head' for faculty member {$user['user_id']} who is also a Program Head.");
            $action = 'Approved';
            $actualUserId = $user['user_id']; // The user signs for themselves.
            $signatoryFirstName = $user['first_name'] ?? null;
            $signatoryLastName = $user['last_name'] ?? null;
            $dateSigned = 'NOW()';
        } else {
            error_log("✅ Assigning designation '{$assignment['designation_name']}' to form {$clearanceFormId}");
            $action = 'Unapplied';
            $actualUserId = null;
            $signatoryFirstName = null;
            $signatoryLastName = null;
            $dateSigned = 'NULL';
        }
        
        // Create signatory entry
        $sql = "
            INSERT INTO clearance_signatories (
                clearance_form_id,
                designation_id,
                actual_user_id,
                signatory_first_name,
                signatory_last_name,
                action,
                created_at,
                date_signed
            ) VALUES (?, ?, ?, ?, ?, ?, NOW(), $dateSigned)
        ";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute([
            $clearanceFormId,
            $assignment['designation_id'],
            $actualUserId,
            $signatoryFirstName,
            $signatoryLastName,
            $action
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
            COUNT(CASE WHEN clearance_form_progress = 'unapplied' THEN 1 END) as unapplied_forms,
            COUNT(CASE WHEN clearance_form_progress = 'in-progress' THEN 1 END) as in_progress_forms,
            COUNT(CASE WHEN clearance_form_progress = 'complete' THEN 1 END) as completed_forms
        FROM clearance_forms
        WHERE clearance_type = ? AND academic_year_id = ? AND semester_id = ?
    ";
    
    $stmt = $connection->prepare($sql);
    $stmt->execute([$clearanceType, $academicYearId, $semesterId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get eligible students for a specific department in batches
 * @param PDO $connection Database connection
 * @param string $clearanceType Sector type (College or Senior High School)
 * @param int $departmentId Department ID
 * @param int $offset Starting offset
 * @param int $limit Number of users to fetch
 * @return array Users array
 */
function getEligibleStudentsBatch($connection, $clearanceType, $departmentId, $offset = 0, $limit = 50) {
    $sql = "
        SELECT DISTINCT u.user_id, u.first_name, u.last_name, u.username, 
               p.program_name as program, s.department_id, d.department_name
        FROM users u
        INNER JOIN students s ON u.user_id = s.user_id
        INNER JOIN departments d ON s.department_id = d.department_id
        LEFT JOIN programs p ON s.program_id = p.program_id
        INNER JOIN sectors sec ON d.sector_id = sec.sector_id
        WHERE s.department_id = ?
          AND sec.sector_name = ?
          AND u.account_status = 'active'
        ORDER BY u.last_name, u.first_name
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $connection->prepare($sql);
    $stmt->execute([$departmentId, $clearanceType, $limit, $offset]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get eligible faculty for a specific department in batches
 * Handles NULL department_id for unassigned faculty
 * @param PDO $connection Database connection
 * @param int|null $departmentId Department ID (NULL for unassigned faculty)
 * @param int $offset Starting offset
 * @param int $limit Number of users to fetch
 * @return array Users array
 */
function getEligibleFacultyBatch($connection, $departmentId, $offset = 0, $limit = 50) {
    if ($departmentId === null) {
        // Get faculty without department assignment
        $sql = "
            SELECT DISTINCT u.user_id, u.first_name, u.last_name, u.username,
                   f.employment_status, f.department_id, NULL as department_name,
                   st.staff_category
            FROM users u
            INNER JOIN faculty f ON u.user_id = f.user_id
            LEFT JOIN staff st ON u.user_id = st.user_id
            WHERE f.department_id IS NULL
              AND u.account_status = 'active'
            ORDER BY u.last_name, u.first_name
            LIMIT ? OFFSET ?
        ";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute([$limit, $offset]);
    } else {
        // Get faculty for specific department
        $sql = "
            SELECT DISTINCT u.user_id, u.first_name, u.last_name, u.username,
                   f.employment_status, f.department_id, d.department_name,
                   st.staff_category
            FROM users u
            INNER JOIN faculty f ON u.user_id = f.user_id
            LEFT JOIN departments d ON f.department_id = d.department_id
            LEFT JOIN staff st ON u.user_id = st.user_id
            WHERE f.department_id = ?
              AND u.account_status = 'active'
            ORDER BY u.last_name, u.first_name
            LIMIT ? OFFSET ?
        ";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute([$departmentId, $limit, $offset]);
    }
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Count eligible students for a specific department
 * @param PDO $connection Database connection
 * @param string $clearanceType Sector type
 * @param int $departmentId Department ID
 * @return int Count of eligible users
 */
function countEligibleStudents($connection, $clearanceType, $departmentId) {
    $sql = "
        SELECT COUNT(DISTINCT u.user_id)
        FROM users u
        INNER JOIN students s ON u.user_id = s.user_id
        INNER JOIN departments d ON s.department_id = d.department_id
        INNER JOIN sectors sec ON d.sector_id = sec.sector_id
        WHERE s.department_id = ?
          AND sec.sector_name = ?
          AND u.account_status = 'active'
    ";
    
    $stmt = $connection->prepare($sql);
    $stmt->execute([$departmentId, $clearanceType]);
    return (int)$stmt->fetchColumn();
}

/**
 * Count eligible faculty for a specific department
 * Handles NULL department_id for unassigned faculty
 * @param PDO $connection Database connection
 * @param int|null $departmentId Department ID (NULL for unassigned faculty)
 * @return int Count of eligible users
 */
function countEligibleFaculty($connection, $departmentId) {
    if ($departmentId === null) {
        // Count faculty without department assignment
        $sql = "
            SELECT COUNT(DISTINCT u.user_id)
            FROM users u
            INNER JOIN faculty f ON u.user_id = f.user_id
            WHERE f.department_id IS NULL
              AND u.account_status = 'active'
        ";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute();
    } else {
        // Count faculty for specific department
        $sql = "
            SELECT COUNT(DISTINCT u.user_id)
            FROM users u
            INNER JOIN faculty f ON u.user_id = f.user_id
            WHERE f.department_id = ?
              AND u.account_status = 'active'
        ";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute([$departmentId]);
    }
    
    return (int)$stmt->fetchColumn();
}

/**
 * Process a single user (create form and assign signatories)
 * @param PDO $connection Database connection
 * @param array $user User data
 * @param int $academicYearId Academic year ID
 * @param int $semesterId Semester ID
 * @param string $clearanceType Sector type
 * @param array $signatoryAssignments Signatory assignments for this sector
 * @return array Result with form_created, signatories_assigned
 */
function processSingleUser($connection, $user, $academicYearId, $semesterId, $clearanceType, $signatoryAssignments) {
    $formsCreated = 0;
    $signatoriesAssigned = 0;

    $userName = ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '');
    $userName = trim($userName) ?: 'Unknown';
    $username = $user['username'] ?? 'N/A';
    $userId = $user['user_id'] ?? 'N/A';

    // Check if form exists
    $existingForm = checkExistingForm($connection, $user['user_id'], $academicYearId, $semesterId, $clearanceType);

    if ($existingForm) {
        $clearanceFormId = $existingForm['clearance_form_id'];

        // Check for missing signatories
        $stmt = $connection->prepare("SELECT designation_id FROM clearance_signatories WHERE clearance_form_id = ?");
        $stmt->execute([$clearanceFormId]);
        $existingDesignationIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $requiredDesignationIds = array_column($signatoryAssignments, 'designation_id');
        $missingDesignationIds = array_diff($requiredDesignationIds, $existingDesignationIds);

        if (empty($missingDesignationIds)) {
            error_log("⏭️  USER SKIPPED: {$userName} (ID: {$userId}, Username: {$username}) - Form {$clearanceFormId} already exists with all signatories");
            return [
                'form_created' => false, 
                'signatories_assigned' => 0,
                'user_name' => $userName,
                'user_id' => $userId,
                'username' => $username,
                'form_id' => $clearanceFormId
            ];
        }

        $missingAssignments = array_filter($signatoryAssignments, function($assignment) use ($missingDesignationIds) {
            return in_array($assignment['designation_id'], $missingDesignationIds);
        });
        $signatoryAssignmentsToProcess = $missingAssignments;
        
        error_log("📝 EXISTING FORM: {$userName} (ID: {$userId}, Username: {$username}) - Form {$clearanceFormId} exists, adding " . count($signatoryAssignmentsToProcess) . " missing signatories");
    } else {
        // Create new form
        $clearanceFormId = createClearanceForm($connection, $user, $academicYearId, $semesterId, $clearanceType);
        $formsCreated = 1;
        $signatoryAssignmentsToProcess = $signatoryAssignments;
        
        error_log("✅ NEW FORM CREATED: {$userName} (ID: {$userId}, Username: {$username}) - Form ID: {$clearanceFormId}");
    }

    // Assign signatories
    $assignedCount = assignSignatoriesToForm($connection, $clearanceFormId, $signatoryAssignmentsToProcess, $user, $clearanceType);
    $signatoriesAssigned = $assignedCount;

    if ($assignedCount > 0) {
        error_log("   └─ Assigned {$assignedCount} signatory/signatories to form {$clearanceFormId}");
    }

    return [
        'form_created' => ($formsCreated > 0),
        'signatories_assigned' => $signatoriesAssigned,
        'user_name' => $userName,
        'user_id' => $userId,
        'username' => $username,
        'form_id' => $clearanceFormId
    ];
}
?>