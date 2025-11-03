<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Suppress PHP warnings to prevent JSON corruption
error_reporting(E_ERROR | E_PARSE);

require_once '../../includes/config/database.php';
require_once '../../includes/classes/Auth.php';

// Check if user is authenticated
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// // Check if user has clearance management permissions
// if (!$auth->hasPermission('manage_clearance_periods')) {
//     http_response_code(403);
//     echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
//     exit;
// }

$db = Database::getInstance();
$connection = $db->getConnection();

// Handle different HTTP methods
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Get clearance periods
        handleGetPeriods($connection);
        break;
        
    case 'POST':
        // Create new clearance period
        handleCreatePeriod($connection);
        break;
        
    case 'PUT':
        // Update clearance period
        handleUpdatePeriod($connection);
        break;
        
    case 'DELETE':
        // Delete clearance period
        handleDeletePeriod($connection);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}

// Get clearance periods
function handleGetPeriods($connection) {
    try {
        // Get sector parameter if provided
        $sector = isset($_GET['sector']) ? $_GET['sector'] : null;
        $semesterId = isset($_GET['semester_id']) ? (int)$_GET['semester_id'] : null;
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        
        // Fetch active periods by sector (for banner)
        $activeSql = "SELECT 
                          cp.period_id,
                          ay.year AS school_year,
                          cp.semester_id,
                          s.semester_name,
                          cp.sector,
                          cp.status,
                          cp.start_date,
                          cp.end_date,
                          cp.ended_at
                      FROM clearance_periods cp
                      JOIN academic_years ay ON cp.academic_year_id = ay.academic_year_id
                      JOIN semesters s ON cp.semester_id = s.semester_id
                      WHERE cp.status = 'Ongoing'";
        
        if ($sector) {
            $activeSql .= " AND cp.sector = ?";
            $activeStmt = $connection->prepare($activeSql);
            $activeStmt->execute([$sector]);
        } else {
            $activeStmt = $connection->query($activeSql);
        }
        
        $activePeriods = $activeStmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch all periods list
        $sql = "SELECT cp.*, ay.year as academic_year, s.semester_name 
                FROM clearance_periods cp
                JOIN academic_years ay ON cp.academic_year_id = ay.academic_year_id
                JOIN semesters s ON cp.semester_id = s.semester_id";
        
        $conditions = [];
        $params = [];

        if ($sector) {
            $conditions[] = "cp.sector = ?";
            $params[] = $sector;
        }
        if ($semesterId) {
            $conditions[] = "cp.semester_id = ?";
            $params[] = $semesterId;
        }
        if ($status) {
            $conditions[] = "cp.status = ?";
            $params[] = $status;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY cp.created_at DESC";
        $stmt = $connection->prepare($sql);
        $stmt->execute($params);
        
        $periods = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Group periods by sector for better organization
        $periodsBySector = [];
        foreach ($periods as $period) {
            $sectorName = $period['sector'];
            if (!isset($periodsBySector[$sectorName])) {
                $periodsBySector[$sectorName] = [];
            }
            $periodsBySector[$sectorName][] = $period;
        }
        
        echo json_encode([
            'success' => true,
            'active_periods' => $activePeriods,
            'periods' => $periods,
            'periods_by_sector' => $periodsBySector,
            'total' => count($periods)
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Create new clearance period
function handleCreatePeriod($connection) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
            return;
        }
        
        // Validate required fields (sector is now required)
        $requiredFields = ['academic_year_id', 'semester_id', 'sector', 'start_date'];
        foreach ($requiredFields as $field) {
            if (empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
                return;
            }
        }
        
        // Validate sector
        $validSectors = ['College', 'Senior High School', 'Faculty'];
        if (!in_array($input['sector'], $validSectors)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid sector. Must be one of: ' . implode(', ', $validSectors)]);
            return;
        }
        
        // Check if there's already an active period for this academic year + semester + sector
        $stmt = $connection->prepare("SELECT COUNT(*) FROM clearance_periods WHERE status = 'Ongoing' AND academic_year_id = ? AND semester_id = ? AND sector = ?");
        $stmt->execute([$input['academic_year_id'], $input['semester_id'], $input['sector']]);
        $activeCount = $stmt->fetchColumn();
        
        if ($activeCount > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "An active clearance period already exists for {$input['sector']} in this academic year and semester."]);
            return;
        }
        
        // Validate dates
        $startDate = new DateTime($input['start_date']);
        $endDate = isset($input['end_date']) && $input['end_date'] !== null && $input['end_date'] !== ''
            ? new DateTime($input['end_date'])
            : (clone $startDate)->add(new DateInterval('P3M')); // Default 3 months
        
        if ($startDate > $endDate) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'End date must be after start date']);
            return;
        }
        
        // Determine status based on requested action
        if (isset($input['action'])) {
            if ($input['action'] === 'start') {
                $newStatus = 'Ongoing';
            } elseif ($input['action'] === 'skip') {
                $newStatus = 'Closed';
            } else {
                $newStatus = 'Not Started';
            }
        } else {
            $newStatus = 'Not Started';
        }

        // Insert new period with sector support
        if ($newStatus === 'Closed') {
            // For skipped periods, set both start_date and end_date to NOW()
            $sql = "INSERT INTO clearance_periods (academic_year_id, semester_id, sector, start_date, end_date, status, created_at) 
                    VALUES (?, ?, ?, NOW(), NOW(), ?, NOW())";
            
            $stmt = $connection->prepare($sql);
            $stmt->execute([
                $input['academic_year_id'],
                $input['semester_id'],
                $input['sector'],
                $newStatus
            ]);
        } else {
            // For normal periods, use provided dates
            $sql = "INSERT INTO clearance_periods (academic_year_id, semester_id, sector, start_date, end_date, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $connection->prepare($sql);
            $stmt->execute([
                $input['academic_year_id'],
                $input['semester_id'],
                $input['sector'],
                $input['start_date'],
                $endDate->format('Y-m-d'),
                $newStatus
            ]);
        }
        
        $periodId = $connection->lastInsertId();
        

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Clearance period created successfully',
            'period_id' => $periodId,
            'sector' => $input['sector'],
            'status' => $newStatus
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Update clearance period
function handleUpdatePeriod($connection) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
            return;
        }
        
        $action = $input['action'] ?? '';
        
        // Handle starting a clearance period (doesn't need period_id)
        if ($action === 'start') {
            error_log("🚀 API DEBUG: Starting clearance period for sector: " . ($input['sector'] ?? 'null'));
            error_log("🚀 API DEBUG: Input data: " . json_encode($input));
            error_log("🚀 API DEBUG: Request method: " . $_SERVER['REQUEST_METHOD']);
            error_log("🚀 API DEBUG: Request URI: " . $_SERVER['REQUEST_URI']);
            error_log("🚀 API DEBUG: User agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));
            
            if (empty($input['sector']) || empty($input['academic_year_id']) || empty($input['semester_id'])) {
                error_log("❌ API DEBUG: Missing required fields");
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Sector, academic_year_id, and semester_id are required for starting clearance period']);
                return;
            }
            
            // Add a simple lock mechanism to prevent concurrent operations
            $lockKey = "sector_start_" . $input['sector'] . "_" . $input['academic_year_id'] . "_" . $input['semester_id'];
            $lockFile = sys_get_temp_dir() . "/" . $lockKey . ".lock";
            
            if (file_exists($lockFile)) {
                error_log("⚠️ API DEBUG: Operation already in progress for $lockKey");
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Operation already in progress for this sector. Please wait and try again.']);
                return;
            }
            
            // Create lock file
            file_put_contents($lockFile, time());
            error_log("🔒 API DEBUG: Created lock file: $lockFile");
            
            try {
                $sector = $input['sector'];
                $academicYearId = (int)$input['academic_year_id'];
                $semesterId = (int)$input['semester_id'];
                $startDate = $input['start_date'] ?? date('Y-m-d');
            
            error_log("🚀 API DEBUG: Processing start for sector: $sector, academic_year_id: $academicYearId, semester_id: $semesterId, start_date: $startDate");
            
            // Check if clearance period already exists
            $stmt = $connection->prepare("SELECT period_id, status FROM clearance_periods WHERE academic_year_id = ? AND semester_id = ? AND sector = ?");
            $stmt->execute([$academicYearId, $semesterId, $sector]);
            $existingPeriod = $stmt->fetch(PDO::FETCH_ASSOC);
            
            error_log("🚀 API DEBUG: Existing period found: " . json_encode($existingPeriod));
            
            if ($existingPeriod) {
                // Update existing period
                if ($existingPeriod['status'] === 'Not Started') {
                    error_log("🚀 API DEBUG: Updating existing period from 'Not Started' to 'Ongoing'");
                    error_log("🚀 API DEBUG: Updating period_id: {$existingPeriod['period_id']} for sector: $sector");
                    
                    // Check current state of all periods before update
                    $checkStmt = $connection->prepare("SELECT period_id, sector, status FROM clearance_periods WHERE academic_year_id = ? AND semester_id = ?");
                    $checkStmt->execute([$academicYearId, $semesterId]);
                    $beforeUpdate = $checkStmt->fetchAll(PDO::FETCH_ASSOC);
                    error_log("🚀 API DEBUG: Periods before update: " . json_encode($beforeUpdate));
                    
                    $stmt = $connection->prepare("UPDATE clearance_periods SET status = 'Ongoing', start_date = ? WHERE period_id = ?");
                    $stmt->execute([$startDate, $existingPeriod['period_id']]);
                    error_log("✅ API DEBUG: Period updated successfully");
                    
                    // Check current state of all periods after update
                    $checkStmt = $connection->prepare("SELECT period_id, sector, status FROM clearance_periods WHERE academic_year_id = ? AND semester_id = ?");
                    $checkStmt->execute([$academicYearId, $semesterId]);
                    $afterUpdate = $checkStmt->fetchAll(PDO::FETCH_ASSOC);
                    error_log("🚀 API DEBUG: Periods after update: " . json_encode($afterUpdate));
                    
                    // NEW: Trigger form distribution by calling the new dedicated API endpoint
                    $distributionUrl = "http://localhost/OnlineClearanceWebsite/api/clearance/form_distribution.php";
                    $distributionData = [
                        'clearance_type' => $sector,
                        'academic_year_id' => $academicYearId,
                        'semester_id' => $semesterId
                    ];
                    $options = [
                        'http' => [
                            'header'  => "Content-type: application/json\r\n",
                            'method'  => 'POST',
                            'content' => json_encode($distributionData),
                            'ignore_errors' => true // To read response body on error
                        ],
                    ];
                    $context  = stream_context_create($options);
                    $result = file_get_contents($distributionUrl, false, $context);
                    $formDistributionResult = json_decode($result, true);

                    $response = [
                        'success' => true, 
                        'message' => 'Clearance period started successfully',
                        'form_distribution' => $formDistributionResult
                    ];
                    error_log("🚀 API DEBUG: Sending success response: " . json_encode($response));
                    echo json_encode($response);
                } else if ($existingPeriod['status'] === 'Paused') {
                    // Allow resuming paused periods
                    error_log("🚀 API DEBUG: Resuming paused period from 'Paused' to 'Ongoing'");
                    error_log("🚀 API DEBUG: Updating period_id: {$existingPeriod['period_id']} for sector: $sector");
                    
                    $stmt = $connection->prepare("UPDATE clearance_periods SET status = 'Ongoing', start_date = ? WHERE period_id = ?");
                    $stmt->execute([$startDate, $existingPeriod['period_id']]);
                    error_log("✅ API DEBUG: Period resumed successfully");

                    // NEW: Trigger form distribution when resuming, just in case it failed before.
                    $distributionUrl = "http://localhost/OnlineClearanceWebsite/api/clearance/form_distribution.php";
                    $distributionData = [
                        'clearance_type' => $sector,
                        'academic_year_id' => $academicYearId,
                        'semester_id' => $semesterId
                    ];
                    $options = [
                        'http' => [
                            'header'  => "Content-type: application/json\r\n",
                            'method'  => 'POST',
                            'content' => json_encode($distributionData),
                            'ignore_errors' => true
                        ],
                    ];
                    $context  = stream_context_create($options);
                    $result = file_get_contents($distributionUrl, false, $context);
                    $formDistributionResult = json_decode($result, true);
                    
                    $response = [
                        'success' => true, 
                        'message' => 'Clearance period resumed successfully',
                        'form_distribution' => $formDistributionResult
                    ];
                    error_log("🚀 API DEBUG: Sending success response: " . json_encode($response));
                    echo json_encode($response);
                } else {
                    error_log("⚠️ API DEBUG: Period already has status: " . $existingPeriod['status']);
                    $response = ['success' => false, 'message' => 'Clearance period is already active or completed'];
                    error_log("🚀 API DEBUG: Sending error response: " . json_encode($response));
                    echo json_encode($response);
                }
            } else {
                // Create new period
                error_log("🚀 API DEBUG: Creating new period");
                $stmt = $connection->prepare("INSERT INTO clearance_periods (academic_year_id, semester_id, sector, status, start_date, created_at) VALUES (?, ?, ?, 'Ongoing', ?, NOW())");
                $stmt->execute([$academicYearId, $semesterId, $sector, $startDate]);
                $periodId = $connection->lastInsertId();
                error_log("✅ API DEBUG: New period created successfully with ID: $periodId");
                
                // NEW: Trigger form distribution by calling the new dedicated API endpoint
                $distributionUrl = "http://localhost/OnlineClearanceWebsite/api/clearance/form_distribution.php";
                $distributionData = [
                    'clearance_type' => $sector,
                    'academic_year_id' => $academicYearId,
                    'semester_id' => $semesterId
                ];
                $options = [
                    'http' => [
                        'header'  => "Content-type: application/json\r\n",
                        'method'  => 'POST',
                        'content' => json_encode($distributionData),
                        'ignore_errors' => true // To read response body on error
                    ],
                ];
                $context  = stream_context_create($options);
                $result = file_get_contents($distributionUrl, false, $context);
                $formDistributionResult = json_decode($result, true);

                echo json_encode([
                    'success' => true, 
                    'message' => 'Clearance period started successfully',
                    'form_distribution' => $formDistributionResult
                ]);
            }
            
            } catch (Exception $e) {
                error_log("❌ API DEBUG: Error in start action: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Internal server error: ' . $e->getMessage()]);
            } finally {
                // Clean up lock file
                if (file_exists($lockFile)) {
                    unlink($lockFile);
                    error_log("🔓 API DEBUG: Removed lock file: $lockFile");
                }
            }
            return;
        }
        
        // Handle skipping a clearance period (doesn't need period_id)
        if ($action === 'skip') {
            error_log("⏭️ API DEBUG: Skipping clearance period for sector: " . ($input['sector'] ?? 'null'));
            error_log("⏭️ API DEBUG: Input data: " . json_encode($input));
            
            if (empty($input['sector']) || empty($input['academic_year_id']) || empty($input['semester_id'])) {
                error_log("❌ API DEBUG: Missing required fields for skip");
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Sector, academic_year_id, and semester_id are required for skipping clearance period']);
                return;
            }
            
            $sector = $input['sector'];
            $academicYearId = (int)$input['academic_year_id'];
            $semesterId = (int)$input['semester_id'];
            $startDate = $input['start_date'] ?? date('Y-m-d');
            
            error_log("⏭️ API DEBUG: Processing skip for sector: $sector, academic_year_id: $academicYearId, semester_id: $semesterId, start_date: $startDate");
            
            // Check if clearance period already exists
            $stmt = $connection->prepare("SELECT period_id, status FROM clearance_periods WHERE academic_year_id = ? AND semester_id = ? AND sector = ?");
            $stmt->execute([$academicYearId, $semesterId, $sector]);
            $existingPeriod = $stmt->fetch(PDO::FETCH_ASSOC);
            
            error_log("⏭️ API DEBUG: Existing period found: " . json_encode($existingPeriod));
            
            if ($existingPeriod) {
                // Update existing period to closed
                error_log("⏭️ API DEBUG: Updating existing period to 'Closed'");
                $stmt = $connection->prepare("UPDATE clearance_periods SET status = 'Closed', start_date = ?, end_date = NOW() WHERE period_id = ?");
                $stmt->execute([$startDate, $existingPeriod['period_id']]);
                error_log("✅ API DEBUG: Period skipped successfully");
                echo json_encode(['success' => true, 'message' => 'Clearance period skipped successfully']);
            } else {
                // Create new period as closed (skipped)
                error_log("⏭️ API DEBUG: Creating new period as 'Closed'");
                $stmt = $connection->prepare("INSERT INTO clearance_periods (academic_year_id, semester_id, sector, status, start_date, end_date, created_at) VALUES (?, ?, ?, 'Closed', ?, NOW(), NOW())");
                $stmt->execute([$academicYearId, $semesterId, $sector, $startDate]);
                error_log("✅ API DEBUG: New period created as skipped");
                echo json_encode(['success' => true, 'message' => 'Clearance period skipped successfully']);
            }
            return;
        }
        
        // Handle pausing a clearance period (needs period_id)
        if ($action === 'pause') {
            error_log("⏸️ API DEBUG: Pausing clearance period");
            error_log("⏸️ API DEBUG: Input data: " . json_encode($input));
            
            if (empty($input['period_id'])) {
                error_log("❌ API DEBUG: Missing period_id for pause");
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Period ID is required for pausing clearance period']);
                return;
            }
            
            $periodId = (int)$input['period_id'];
            
            // Check if period exists and is ongoing
            $stmt = $connection->prepare("SELECT period_id, status FROM clearance_periods WHERE period_id = ?");
            $stmt->execute([$periodId]);
            $period = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$period) {
                error_log("❌ API DEBUG: Period not found: $periodId");
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Clearance period not found']);
                return;
            }
            
            if ($period['status'] !== 'Ongoing') {
                error_log("⚠️ API DEBUG: Cannot pause period with status: " . $period['status']);
                echo json_encode(['success' => false, 'message' => 'Only ongoing clearance periods can be paused']);
                return;
            }
            
            // Update status to Paused
            $stmt = $connection->prepare("UPDATE clearance_periods SET status = 'Paused' WHERE period_id = ?");
            $stmt->execute([$periodId]);
            
            error_log("✅ API DEBUG: Period paused successfully");
            echo json_encode(['success' => true, 'message' => 'Clearance period paused successfully']);
            return;
        }
        
        // Handle closing a clearance period (needs period_id)
        if ($action === 'close') {
            error_log("🛑 API DEBUG: Closing clearance period");
            error_log("🛑 API DEBUG: Input data: " . json_encode($input));
            
            if (empty($input['period_id'])) {
                error_log("❌ API DEBUG: Missing period_id for close");
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Period ID is required for closing clearance period']);
                return;
            }
            
            $periodId = (int)$input['period_id'];
            
            // Check if period exists
            $stmt = $connection->prepare("SELECT period_id, status FROM clearance_periods WHERE period_id = ?");
            $stmt->execute([$periodId]);
            $period = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$period) {
                error_log("❌ API DEBUG: Period not found: $periodId");
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Clearance period not found']);
                return;
            }
            
            if ($period['status'] === 'Closed') {
                error_log("⚠️ API DEBUG: Period already closed");
                echo json_encode(['success' => false, 'message' => 'Clearance period is already closed']);
                return;
            }
            
            // Update status to Closed and set end_date
            $stmt = $connection->prepare("UPDATE clearance_periods SET status = 'Closed', end_date = NOW() WHERE period_id = ?");
            $stmt->execute([$periodId]);
            
            error_log("✅ API DEBUG: Period closed successfully");
            echo json_encode(['success' => true, 'message' => 'Clearance period closed successfully']);
            return;
        }
        
        // Handle semester-level operations first (these don't need period_id)
        if ($action === 'activate_semester') {
            if (empty($input['semester_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Semester ID is required for semester activation']);
                return;
            }
            
            $semesterId = (int)$input['semester_id'];
            
            // Check if semester exists
            $stmt = $connection->prepare("SELECT * FROM semesters WHERE semester_id = ?");
            $stmt->execute([$semesterId]);
            $semester = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$semester) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Semester not found']);
                return;
            }
            
            // First, deactivate all other semesters in the same academic year to avoid conflicts
            $stmt = $connection->prepare("UPDATE semesters SET is_active = 0 WHERE academic_year_id = ? AND semester_id != ?");
            $stmt->execute([$semester['academic_year_id'], $semesterId]);
            $deactivatedSemesters = $stmt->rowCount();
            
            if ($deactivatedSemesters > 0) {
                error_log("Deactivated {$deactivatedSemesters} other semesters during activation");
            }
            
            // Then, deactivate all clearance periods in the same academic year to avoid conflicts
            $stmt = $connection->prepare("UPDATE clearance_periods SET status = 'Closed', end_date = NOW() WHERE academic_year_id = ? AND status = 'Ongoing'");
            $stmt->execute([$semester['academic_year_id']]);
            $closedPeriods = $stmt->rowCount();
            
            if ($closedPeriods > 0) {
                error_log("Closed {$closedPeriods} ongoing clearance periods during semester activation");
            }
            
            // Activate the requested semester
            $stmt = $connection->prepare("UPDATE semesters SET is_active = 1 WHERE semester_id = ?");
            $stmt->execute([$semesterId]);

            // Create clearance periods for all sectors (College, SHS, Faculty) with status = 'Not Started'
            // and is_active = 0. This prepares them for manual activation by the admin.
            $sectors = ['College', 'Senior High School', 'Faculty'];
            $createdPeriods = 0;
            
            foreach ($sectors as $sector) {
                // Check if a clearance period already exists for this sector, semester, and academic year
                $stmt = $connection->prepare("SELECT COUNT(*) FROM clearance_periods WHERE academic_year_id = ? AND semester_id = ? AND sector = ?");
                $stmt->execute([$semester['academic_year_id'], $semesterId, $sector]);
                $exists = $stmt->fetchColumn();
                
                if ($exists == 0) {
                    // Create a new, non-active clearance period
                    $stmt = $connection->prepare("INSERT INTO clearance_periods (academic_year_id, semester_id, sector, status, is_active, created_at) VALUES (?, ?, ?, 'Not Started', 0, NOW())");
                    $stmt->execute([$semester['academic_year_id'], $semesterId, $sector]);
                    $createdPeriods++;
                }
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Semester activated successfully. Clearance periods have been created and are ready to be started individually.',
                'created_periods' => $createdPeriods
            ]);
            return;
        }
        
        // Handle semester ending
        if ($action === 'end_semester') {
            if (empty($input['semester_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Semester ID is required for semester ending']);
                return;
            }
            
            $semesterId = (int)$input['semester_id'];
            
            // Check if semester exists
            $stmt = $connection->prepare("SELECT * FROM semesters WHERE semester_id = ?");
            $stmt->execute([$semesterId]);
            $semester = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$semester) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Semester not found']);
                return;
            }
            
            // Deactivate the semester
            $stmt = $connection->prepare("UPDATE semesters SET is_active = 0 WHERE semester_id = ?");
            $stmt->execute([$semesterId]);
            
            echo json_encode(['success' => true, 'message' => 'Semester ended successfully']);
            return;
        }
        
        // Handle cascade close periods
        if ($action === 'cascade_close_periods') {
            if (empty($input['semester_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Semester ID is required for cascade close operation']);
                return;
            }
            
            $semesterId = (int)$input['semester_id'];
            
            // Close all clearance periods under this semester
            $stmt = $connection->prepare("UPDATE clearance_periods SET status = 'Closed', end_date = NOW() WHERE semester_id = ? AND status IN ('Ongoing', 'Not Started')");
            $stmt->execute([$semesterId]);
            $closedCount = $stmt->rowCount();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Cascade close completed successfully',
                'closed_periods' => $closedCount
            ]);
            return;
        }
        
        // For all other operations, require period_id
        if (empty($input['period_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Period ID is required']);
            return;
        }
        
        $periodId = (int)$input['period_id'];
        
        // Check if period exists
        $stmt = $connection->prepare("SELECT * FROM clearance_periods WHERE period_id = ?");
        $stmt->execute([$periodId]);
        $existingPeriod = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$existingPeriod) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Clearance period not found']);
            return;
        }
        
        // Handle period-level actions
        if (isset($input['action'])) {
            $action = $input['action'];
            
            if ($action === 'activate') {
                // Allow reactivation of the SAME term when it is currently deactivated,
                // but still block if another term is active. When activating a DIFFERENT term,
                // require that all other terms are ended (no active or deactivated terms).
                if (strtolower($existingPeriod['status']) === 'deactivated') {
                    // Reactivating same term → only ensure no other term is active
                    $chk = $connection->prepare("SELECT COUNT(*) FROM clearance_periods WHERE academic_year_id = ? AND period_id != ? AND status = 'active'");
                    $chk->execute([$existingPeriod['academic_year_id'], $periodId]);
                    if ((int)$chk->fetchColumn() > 0) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Another term is currently active. End or deactivate it first.']);
                        return;
                    }
                } else {
                    // Activating a different term → no other active or deactivated terms allowed
                    $chk = $connection->prepare("SELECT COUNT(*) FROM clearance_periods WHERE academic_year_id = ? AND period_id != ? AND status IN ('active','deactivated')");
                    $chk->execute([$existingPeriod['academic_year_id'], $periodId]);
                    if ((int)$chk->fetchColumn() > 0) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Another term in this school year is not ended. End it first before activating a different term.']);
                        return;
                    }
                }
                // Activate this period; deactivate others in same academic year
                // Ensure start_date exists
                if (empty($existingPeriod['start_date'])) {
                    $stmt = $connection->prepare("UPDATE clearance_periods SET start_date = CURDATE() WHERE period_id = ?");
                    $stmt->execute([$periodId]);
                }
                // Activate
                $stmt = $connection->prepare("UPDATE clearance_periods SET is_active = 1, status = 'active', ended_at = NULL WHERE period_id = ?");
                $stmt->execute([$periodId]);
                // Deactivate others in same academic year (but not those that are already ended)
                $stmt = $connection->prepare("UPDATE clearance_periods SET is_active = 0, status = 'deactivated' WHERE period_id != ? AND academic_year_id = ? AND status != 'ended'");
                $stmt->execute([$periodId, $existingPeriod['academic_year_id']]);

                // NEW: Reset all clearance forms for new term
                resetClearanceFormsForNewTerm($connection, $existingPeriod['academic_year_id'], $existingPeriod['semester_id']);

                echo json_encode(['success' => true, 'message' => 'Period activated and clearance forms reset']);
                return;
            } elseif ($action === 'deactivate') {
                $stmt = $connection->prepare("UPDATE clearance_periods SET is_active = 0, status = 'deactivated' WHERE period_id = ?");
                $stmt->execute([$periodId]);
                echo json_encode(['success' => true, 'message' => 'Period deactivated']);
                return;
            } elseif ($action === 'end') {
                $stmt = $connection->prepare("UPDATE clearance_periods SET is_active = 0, status = 'ended', ended_at = NOW(), end_date = CURDATE() WHERE period_id = ?");
                $stmt->execute([$periodId]);
                echo json_encode(['success' => true, 'message' => 'Period ended']);
                return;
            }
        }

        // If making this period active by field update, deactivate others in same academic year (but not those that are already ended)
        if (isset($input['is_active']) && $input['is_active'] && !$existingPeriod['is_active']) {
            $stmt = $connection->prepare("UPDATE clearance_periods SET is_active = 0, status = 'deactivated' WHERE period_id != ? AND academic_year_id = ? AND status != 'ended'");
            $stmt->execute([$periodId, $existingPeriod['academic_year_id']]);
            // Also set status active on this period if caller forgot to include it
            $input['status'] = 'active';
        }
        
        // Build update query
        $updateFields = [];
        $params = [];
        
        $allowedFields = ['academic_year_id', 'semester_id', 'start_date', 'end_date', 'is_active', 'status'];
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateFields[] = "$field = ?";
                $params[] = $input[$field];
            }
        }
        
        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No fields to update']);
            return;
        }
        
        $updateFields[] = "updated_at = NOW()";
        $params[] = $periodId;
        
        $sql = "UPDATE clearance_periods SET " . implode(', ', $updateFields) . " WHERE period_id = ?";
        $stmt = $connection->prepare($sql);
        $stmt->execute($params);
        
        echo json_encode([
            'success' => true,
            'message' => 'Clearance period updated successfully'
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Delete clearance period
function handleDeletePeriod($connection) {
    try {
        $periodId = null;
        
        // Try to get from query string first
        if (isset($_GET['period_id'])) {
            $periodId = (int)$_GET['period_id'];
        } else {
            // Try to get from request body
            $input = json_decode(file_get_contents('php://input'), true);
            if ($input && isset($input['period_id'])) {
                $periodId = (int)$input['period_id'];
            }
        }
        
        if (!$periodId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Period ID is required']);
            return;
        }
        
        // Check if period exists and is not active
        $stmt = $connection->prepare("SELECT is_active FROM clearance_periods WHERE period_id = ?");
        $stmt->execute([$periodId]);
        $period = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$period) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Clearance period not found']);
            return;
        }
        
        if ($period['is_active']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Cannot delete active clearance period. Deactivate it first.']);
            return;
        }
        
        // Check if period has any applications
        $stmt = $connection->prepare("SELECT COUNT(*) FROM clearance_applications WHERE period_id = ?");
        $stmt->execute([$periodId]);
        $applicationCount = $stmt->fetchColumn();
        
        if ($applicationCount > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Cannot delete period with existing applications']);
            return;
        }
        
        // Delete period
        $stmt = $connection->prepare("DELETE FROM clearance_periods WHERE period_id = ?");
        $stmt->execute([$periodId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Clearance period deleted successfully'
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Helper function to reset all clearance forms for new term
function resetClearanceFormsForNewTerm($connection, $academicYearId, $semesterId) {
    try {
        // 1. Delete all existing clearance forms for this period
        $deleteFormsStmt = $connection->prepare("DELETE FROM clearance_forms WHERE academic_year_id = ? AND semester_id = ?");
        $deleteFormsStmt->execute([$academicYearId, $semesterId]);
        
        // 2. Get all active users (faculty and students)
        $usersStmt = $connection->prepare("
            SELECT u.user_id, r.role_name 
            FROM users u 
            JOIN roles r ON u.role_id = r.role_id 
            WHERE u.account_status = 'active'
        ");
        $usersStmt->execute();
        $users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 3. Create fresh clearance forms for all active users
        $insertFormStmt = $connection->prepare("
            INSERT INTO clearance_forms (user_id, academic_year_id, semester_id, clearance_type, status, created_at, updated_at) 
            VALUES (?, ?, ?, ?, 'Unapplied', NOW(), NOW())
        ");
        
        $insertSignatoryStmt = $connection->prepare("
            INSERT INTO clearance_signatories (clearance_form_id, designation_id, action, created_at, updated_at) 
            VALUES (?, ?, 'Unapplied', NOW(), NOW())
        ");
        
        foreach ($users as $user) {
            $clearanceType = ($user['role_name'] === 'Faculty') ? 'Faculty' : 'Student';
            
            // Insert clearance form
            $insertFormStmt->execute([$user['user_id'], $academicYearId, $semesterId, $clearanceType]);
            $formId = $connection->lastInsertId();
            
            // Get assigned signatories for this clearance type
            $signatoriesStmt = $connection->prepare("
                SELECT DISTINCT sa.designation_id 
                FROM signatory_assignments sa
                JOIN designations d ON d.designation_id = sa.designation_id
                WHERE sa.clearance_type = ? 
                AND sa.is_active = 1 
                AND d.is_active = 1
            ");
            $signatoriesStmt->execute([$clearanceType]);
            $signatories = $signatoriesStmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Create signatory entries for all assigned designations
            foreach ($signatories as $designationId) {
                $insertSignatoryStmt->execute([$formId, $designationId]);
            }
        }
        
    } catch (Exception $e) {
        // Log error but don't fail the activation
        error_log("Error resetting clearance forms for new term: " . $e->getMessage());
    }
}

?>
