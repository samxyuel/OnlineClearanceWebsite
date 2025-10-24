<?php
/**
 * Sector-Based Clearance Periods API
 * Handles sector-specific clearance period management
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
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

$db = Database::getInstance();
$connection = $db->getConnection();

// Handle different HTTP methods
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        handleGetSectorPeriods($connection);
        break;
        
    case 'POST':
        handleCreateSectorPeriod($connection);
        break;
        
    case 'PUT':
        handleUpdateSectorPeriod($connection);
        break;
        
    case 'DELETE':
        handleDeleteSectorPeriod($connection);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}

// Get sector-specific clearance periods
function handleGetSectorPeriods($connection) {
    try {
        $academicYearId = $_GET['academic_year_id'] ?? null;
        $semesterId = $_GET['semester_id'] ?? null;
        
        // Build query
        $sql = "SELECT 
                    cp.period_id,
                    cp.academic_year_id,
                    cp.semester_id,
                    cp.sector,
                    cp.status,
                    cp.start_date,
                    cp.end_date,
                    cp.ended_at,
                    cp.created_at,
                    cp.updated_at,
                    ay.year as academic_year,
                    s.semester_name
                FROM clearance_periods cp
                JOIN academic_years ay ON cp.academic_year_id = ay.academic_year_id
                JOIN semesters s ON cp.semester_id = s.semester_id
                WHERE 1=1";
        
        $params = [];
        
        if ($academicYearId) {
            $sql .= " AND cp.academic_year_id = ?";
            $params[] = $academicYearId;
        }
        
        if ($semesterId) {
            $sql .= " AND cp.semester_id = ?";
            $params[] = $semesterId;
        }
        
        $sql .= " ORDER BY cp.created_at DESC";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute($params);
        $periods = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Group periods by sector
        $periodsBySector = [];
        foreach ($periods as $period) {
            $sector = $period['sector'];
            if (!isset($periodsBySector[$sector])) {
                $periodsBySector[$sector] = [];
            }
            $periodsBySector[$sector][] = $period;
        }
        
        // Get statistics for each sector
        $statistics = getSectorStatistics($connection, $periods);
        
        echo json_encode([
            'success' => true,
            'periods' => $periods,
            'periods_by_sector' => $periodsBySector,
            'statistics' => $statistics,
            'total' => count($periods)
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Create sector-specific clearance period
function handleCreateSectorPeriod($connection) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
            return;
        }
        
        // Validate required fields
        $requiredFields = ['academic_year_id', 'semester_id', 'sector'];
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
        
        // Check if period already exists for this combination
        $stmt = $connection->prepare("
            SELECT period_id FROM clearance_periods 
            WHERE academic_year_id = ? AND semester_id = ? AND sector = ?
        ");
        $stmt->execute([$input['academic_year_id'], $input['semester_id'], $input['sector']]);
        
        if ($stmt->rowCount() > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "A clearance period already exists for {$input['sector']} in this academic year and semester."]);
            return;
        }
        
        // Set default dates if not provided
        $startDate = $input['start_date'] ?? date('Y-m-d');
        $endDate = $input['end_date'] ?? date('Y-m-d', strtotime('+3 months'));
        
        // Insert new period
        $sql = "INSERT INTO clearance_periods (academic_year_id, semester_id, sector, start_date, end_date, status, created_at) 
                VALUES (?, ?, ?, ?, ?, 'Not Started', NOW())";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute([
            $input['academic_year_id'],
            $input['semester_id'],
            $input['sector'],
            $startDate,
            $endDate
        ]);
        
        $periodId = $connection->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Sector clearance period created successfully',
            'period_id' => $periodId,
            'sector' => $input['sector']
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Update sector-specific clearance period
function handleUpdateSectorPeriod($connection) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || empty($input['period_id'])) {
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
        
        // Handle specific actions
        if (isset($input['action'])) {
            $action = $input['action'];
            
            if ($action === 'start') {
                return startSectorPeriod($connection, $periodId, $existingPeriod);
            } elseif ($action === 'pause') {
                return pauseSectorPeriod($connection, $periodId);
            } elseif ($action === 'resume') {
                return resumeSectorPeriod($connection, $periodId);
            } elseif ($action === 'close') {
                // Note: The 'close' action in sector-periods.php is different from the one in periods.php
                // This one is for closing a *sector-specific* period, not a whole term.
                // The logic in periods.php for 'end_semester' and 'cascade_close_periods' handles term-level closing.
                return closeSectorPeriod($connection, $periodId);
            }
        }
        
        // Update fields
        $updateFields = [];
        $params = [];
        
        $allowedFields = ['start_date', 'end_date', 'status'];
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
            'message' => 'Sector clearance period updated successfully'
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Start sector clearance period
function startSectorPeriod($connection, $periodId, $period) {
    try {
        // Check if there are any ongoing periods for the same sector
        $stmt = $connection->prepare("
            SELECT COUNT(*) FROM clearance_periods 
            WHERE sector = ? AND status = 'Ongoing' AND period_id != ?
        ");
        $stmt->execute([$period['sector'], $periodId]);
        
        if ($stmt->fetchColumn() > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Another {$period['sector']} clearance period is already ongoing."]);
            return;
        }
        
        // Update period status
        $stmt = $connection->prepare("
            UPDATE clearance_periods 
            SET status = 'Ongoing', start_date = CURDATE(), updated_at = NOW() 
            WHERE period_id = ?
        ");
        $stmt->execute([$periodId]);
        
        // The form distribution is now handled by the form_distribution.php API,
        // which is called from periods.php when a period is started.
        
        echo json_encode([
            'success' => true,
            'message' => "{$period['sector']} clearance period started successfully"
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Pause sector clearance period
function pauseSectorPeriod($connection, $periodId) {
    try {
        $stmt = $connection->prepare("
            UPDATE clearance_periods 
            SET status = 'Paused', updated_at = NOW() 
            WHERE period_id = ?
        ");
        $stmt->execute([$periodId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Clearance period paused successfully'
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Resume sector clearance period
function resumeSectorPeriod($connection, $periodId) {
    try {
        $stmt = $connection->prepare("
            UPDATE clearance_periods 
            SET status = 'Ongoing', updated_at = NOW() 
            WHERE period_id = ?
        ");
        $stmt->execute([$periodId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Clearance period resumed successfully'
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Close sector clearance period
function closeSectorPeriod($connection, $periodId) {
    try {
        $stmt = $connection->prepare("
            UPDATE clearance_periods 
            SET status = 'Closed', end_date = CURDATE(), ended_at = NOW(), updated_at = NOW() 
            WHERE period_id = ? AND status != 'Closed'
        ");
        $stmt->execute([$periodId]);
        
        // Update any pending forms to rejected
        $stmt = $connection->prepare("
            UPDATE clearance_forms cf
            SET cf.clearance_form_progress = 'in-progress',
                cf.rejected_at = NOW(),
                cf.updated_at = NOW()
            WHERE cf.academic_year_id = (SELECT academic_year_id FROM clearance_periods WHERE period_id = ?)
              AND cf.semester_id = (SELECT semester_id FROM clearance_periods WHERE period_id = ?)
              AND cf.clearance_type = (SELECT sector FROM clearance_periods WHERE period_id = ?)
              AND cf.clearance_form_progress IN ('unapplied', 'in-progress')

        ");
        $stmt->execute([$periodId, $periodId, $periodId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Clearance period closed successfully. Pending forms have been updated.'
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Delete sector clearance period
function handleDeleteSectorPeriod($connection) {
    try {
        $periodId = $_GET['period_id'] ?? null;
        
        if (!$periodId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Period ID is required']);
            return;
        }
        
        // Check if period exists and is not active
        $stmt = $connection->prepare("SELECT status FROM clearance_periods WHERE period_id = ?");
        $stmt->execute([$periodId]);
        $period = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$period) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Clearance period not found']);
            return;
        }
        
        if ($period['status'] === 'Ongoing') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Cannot delete ongoing clearance period. Close it first.']);
            return;
        }
        
        // Check if period has any clearance forms
        $stmt = $connection->prepare("
            SELECT COUNT(*) FROM clearance_forms cf
            JOIN clearance_periods cp ON (
                cf.academic_year_id = cp.academic_year_id 
                AND cf.semester_id = cp.semester_id 
                AND cf.clearance_type = cp.sector
            )
            WHERE cp.period_id = ?
        ");
        $stmt->execute([$periodId]);
        $formCount = $stmt->fetchColumn();
        
        if ($formCount > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Cannot delete period with existing clearance forms']);
            return;
        }
        
        // Delete period
        $stmt = $connection->prepare("DELETE FROM clearance_periods WHERE period_id = ?");
        $stmt->execute([$periodId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Sector clearance period deleted successfully'
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Helper function to get sector statistics
function getSectorStatistics($connection, $periods) {
    $statistics = [];

        $periodIds = array_map(function($p) { return $p['period_id']; }, $periods);

    if (empty($periodIds)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($periodIds), '?'));

    $sql = "
        SELECT
            cp.sector,
            COUNT(DISTINCT cp.period_id) as total_periods,
            SUM(CASE WHEN cp.status = 'Ongoing' THEN 1 ELSE 0 END) as ongoing_periods,
            SUM(CASE WHEN cp.status = 'Closed' THEN 1 ELSE 0 END) as closed_periods,
            COUNT(cf.clearance_form_id) as total_forms,
            SUM(CASE WHEN cf.clearance_form_progress = 'in-progress' THEN 1 ELSE 0 END) as pending_forms,
            SUM(CASE WHEN cf.clearance_form_progress = 'complete' THEN 1 ELSE 0 END) as completed_forms
        FROM clearance_periods cp
        LEFT JOIN clearance_forms cf ON cp.academic_year_id = cf.academic_year_id
                                    AND cp.semester_id = cf.semester_id
                                    AND cp.sector = cf.clearance_type
        WHERE cp.period_id IN ($placeholders)
        GROUP BY cp.sector
    ";

    $stmt = $connection->prepare($sql);
    $stmt->execute($periodIds);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($results as $row) {
        $statistics[$row['sector']] = [
            'total_periods' => (int)$row['total_periods'],
            'ongoing_periods' => (int)$row['ongoing_periods'],
            'closed_periods' => (int)$row['closed_periods'],
            'total_forms' => (int)$row['total_forms'],
            'pending_forms' => (int)$row['pending_forms'],
            'completed_forms' => (int)$row['completed_forms']
        ];
    }

    return $statistics;
}
?>
